<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Http\Controllers;

use App\Domain\Admin\Services\ActivityLogService;
use App\Domain\Marketing\Http\Requests\{ReorderBannerRequest, StoreBannerRequest, UpdateBannerRequest};
use App\Domain\Marketing\Models\Banner;
use App\Domain\Marketing\Services\BannerImageService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function __construct(
        private readonly BannerImageService $imageService,
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    /**
     * Display a listing of banners.
     */
    public function index(): View
    {
        $bannersQuery = Banner::query();

        if ($search = request('search')) {
            $bannersQuery->where('title', 'like', '%' . $search . '%');
        }

        if ($status = request('status')) {
            $now = now();

            $bannersQuery->when($status === 'active', fn ($query) => $query->displayable());
            $bannersQuery->when($status === 'inactive', fn ($query) => $query->where('is_active', false));
            $bannersQuery->when(
                $status === 'scheduled',
                fn ($query) => $query->where('is_active', true)
                    ->whereNotNull('starts_at')
                    ->where('starts_at', '>', $now),
            );
            $bannersQuery->when(
                $status === 'expired',
                fn ($query) => $query->where('is_active', true)
                    ->whereNotNull('ends_at')
                    ->where('ends_at', '<', $now),
            );
        }

        $banners = $bannersQuery->orderBy('position')->paginate(10)->withQueryString();

        return view('admin.banners.index', compact('banners'));
    }

    /**
     * Show the form for creating a new banner.
     */
    public function create(): View
    {
        return view('admin.banners.create');
    }

    /**
     * Store a newly created banner in storage.
     */
    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $imageDesktopPath = $this->imageService->storeDesktop($request->file('image_desktop'));

        $imageMobilePath = null;

        if ($request->hasFile('image_mobile')) {
            $imageMobilePath = $this->imageService->storeMobile($request->file('image_mobile'));
        }

        $banner = Banner::create([
            'title'         => $validated['title'],
            'image_desktop' => $imageDesktopPath,
            'image_mobile'  => $imageMobilePath,
            'link'          => $validated['link'] ?? null,
            'alt_text'      => $validated['alt_text'] ?? null,
            'is_active'     => $request->boolean('is_active'),
            'starts_at'     => $validated['starts_at'] ?? null,
            'ends_at'       => $validated['ends_at'] ?? null,
            'created_by'    => auth('admin')->id(),
        ]);

        $this->activityLogService->logCreated(
            subject: $banner,
            description: "Criou o banner \"{$banner->title}\"",
        );

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner criado com sucesso!');
    }

    /**
     * Show the form for editing the specified banner.
     */
    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', compact('banner'));
    }

    /**
     * Update the specified banner in storage.
     */
    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'title'      => $validated['title'],
            'link'       => $validated['link'] ?? null,
            'alt_text'   => $validated['alt_text'] ?? null,
            'is_active'  => $request->boolean('is_active'),
            'starts_at'  => $validated['starts_at'] ?? null,
            'ends_at'    => $validated['ends_at'] ?? null,
            'updated_by' => auth('admin')->id(),
        ];

        if ($request->hasFile('image_desktop')) {
            $this->imageService->delete($banner->image_desktop);
            $data['image_desktop'] = $this->imageService->storeDesktop($request->file('image_desktop'));
        }

        if ($request->hasFile('image_mobile')) {
            if ($banner->image_mobile !== null) {
                $this->imageService->delete($banner->image_mobile);
            }
            $data['image_mobile'] = $this->imageService->storeMobile($request->file('image_mobile'));
        }

        $banner->update($data);

        $this->activityLogService->logUpdated(
            subject: $banner,
            description: "Atualizou o banner \"{$banner->title}\"",
        );

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner atualizado com sucesso!');
    }

    /**
     * Remove the specified banner from storage.
     */
    public function destroy(Banner $banner): RedirectResponse
    {
        $title = $banner->title;

        $this->imageService->deleteBannerImages($banner);

        $banner->delete();

        $this->activityLogService->logDeleted(
            subject: $banner,
            description: "Excluiu o banner \"{$title}\"",
        );

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner excluido com sucesso!');
    }

    /**
     * Toggle banner active status.
     */
    public function toggleActive(Banner $banner): RedirectResponse
    {
        $previousStatus = $banner->is_active ? 'active' : 'inactive';
        $banner->update([
            'is_active'  => !$banner->is_active,
            'updated_by' => auth('admin')->id(),
        ]);

        $newStatus = $banner->is_active ? 'active' : 'inactive';

        $this->activityLogService->logStatusChanged($banner, $previousStatus, $newStatus);

        return redirect()->back()->with('success', 'Status do banner atualizado com sucesso!');
    }

    /**
     * Duplicate the specified banner.
     */
    public function duplicate(Banner $banner): RedirectResponse
    {
        $desktopCopy = $this->imageService->duplicate($banner->image_desktop);
        $mobileCopy  = $banner->image_mobile !== null
            ? $this->imageService->duplicate($banner->image_mobile)
            : null;

        $newBanner = Banner::create([
            'title'         => $banner->title . ' (Cópia)',
            'image_desktop' => $desktopCopy,
            'image_mobile'  => $mobileCopy,
            'link'          => $banner->link,
            'alt_text'      => $banner->alt_text,
            'is_active'     => false,
            'starts_at'     => $banner->starts_at,
            'ends_at'       => $banner->ends_at,
            'created_by'    => auth('admin')->id(),
        ]);

        $this->activityLogService->logCreated(
            subject: $newBanner,
            description: "Duplicou o banner \"{$banner->title}\"",
        );

        return redirect()->route('admin.banners.edit', $newBanner)
            ->with('success', 'Banner duplicado com sucesso!');
    }

    /**
     * Reorder banners based on drag-and-drop.
     */
    public function reorder(ReorderBannerRequest $request): RedirectResponse
    {
        $order = $request->validated('order');

        DB::transaction(function () use ($order) {
            foreach ($order as $index => $bannerId) {
                Banner::where('id', $bannerId)->update(['position' => $index + 1]);
            }
        });

        return redirect()->back()->with('success', 'Ordem dos banners atualizada com sucesso!');
    }
}
