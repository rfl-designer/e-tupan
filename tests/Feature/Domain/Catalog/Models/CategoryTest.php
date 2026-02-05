<?php declare(strict_types = 1);

use App\Domain\Catalog\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Category Model', function () {
    describe('fillable attributes', function () {
        it('has correct fillable attributes', function () {
            $category = new Category();

            expect($category->getFillable())->toBe([
                'parent_id',
                'name',
                'slug',
                'description',
                'image',
                'meta_title',
                'meta_description',
                'position',
                'is_active',
            ]);
        });
    });

    describe('casts', function () {
        it('casts is_active to boolean', function () {
            $category = Category::factory()->create(['is_active' => 1]);

            expect($category->is_active)->toBeBool()
                ->and($category->is_active)->toBeTrue();
        });

        it('casts position to integer', function () {
            $category = Category::factory()->create(['position' => '5']);

            expect($category->position)->toBeInt()
                ->and($category->position)->toBe(5);
        });
    });

    describe('slug generation', function () {
        it('generates slug automatically from name', function () {
            $category = Category::create(['name' => 'Eletrônicos e Informática']);

            expect($category->slug)->toBe('eletronicos-e-informatica');
        });

        it('generates unique slug when duplicate exists', function () {
            Category::create(['name' => 'Eletrônicos', 'slug' => 'eletronicos']);
            $category = Category::create(['name' => 'Eletrônicos']);

            expect($category->slug)->toBe('eletronicos-1');
        });

        it('updates slug when name changes', function () {
            $category = Category::create(['name' => 'Eletrônicos']);
            $category->update(['name' => 'Informática']);

            expect($category->fresh()->slug)->toBe('informatica');
        });

        it('does not change slug if manually set', function () {
            $category = Category::create([
                'name' => 'Eletrônicos',
                'slug' => 'custom-slug',
            ]);

            expect($category->slug)->toBe('custom-slug');
        });
    });

    describe('relationships', function () {
        it('belongs to a parent category', function () {
            $parent = Category::factory()->create();
            $child  = Category::factory()->create(['parent_id' => $parent->id]);

            expect($child->parent)->toBeInstanceOf(Category::class)
                ->and($child->parent->id)->toBe($parent->id);
        });

        it('has many children categories', function () {
            $parent   = Category::factory()->create();
            $children = Category::factory()->count(3)->create(['parent_id' => $parent->id]);

            expect($parent->children)->toHaveCount(3)
                ->and($parent->children->first())->toBeInstanceOf(Category::class);
        });

        it('has many products through pivot table', function () {
            $category = Category::factory()->create();

            expect($category->products())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        });
    });

    describe('scopes', function () {
        it('filters active categories', function () {
            Category::factory()->count(2)->create(['is_active' => true]);
            Category::factory()->count(3)->create(['is_active' => false]);

            expect(Category::active()->count())->toBe(2);
        });

        it('filters root categories', function () {
            $root1 = Category::factory()->create(['parent_id' => null]);
            $root2 = Category::factory()->create(['parent_id' => null]);
            Category::factory()->create(['parent_id' => $root1->id]);

            expect(Category::root()->count())->toBe(2);
        });
    });

    describe('depth calculation', function () {
        it('returns depth 1 for root category', function () {
            $category = Category::factory()->create(['parent_id' => null]);

            expect($category->getDepth())->toBe(1);
        });

        it('returns depth 2 for first level child', function () {
            $parent = Category::factory()->create();
            $child  = Category::factory()->create(['parent_id' => $parent->id]);

            expect($child->getDepth())->toBe(2);
        });

        it('returns depth 3 for second level child', function () {
            $grandparent = Category::factory()->create();
            $parent      = Category::factory()->create(['parent_id' => $grandparent->id]);
            $child       = Category::factory()->create(['parent_id' => $parent->id]);

            expect($child->getDepth())->toBe(3);
        });
    });

    describe('hierarchy validation', function () {
        it('allows children for categories not at max depth', function () {
            $category = Category::factory()->create();

            expect($category->canHaveChildren())->toBeTrue();
        });

        it('prevents children for categories at max depth', function () {
            $level1 = Category::factory()->create();
            $level2 = Category::factory()->create(['parent_id' => $level1->id]);
            $level3 = Category::factory()->create(['parent_id' => $level2->id]);

            expect($level3->canHaveChildren())->toBeFalse();
        });

        it('allows null as parent', function () {
            $category = Category::factory()->create();

            expect($category->canBeParent(null))->toBeTrue();
        });

        it('prevents category from being its own parent', function () {
            $category = Category::factory()->create();

            expect($category->canBeParent($category))->toBeFalse();
        });

        it('prevents circular reference', function () {
            $parent = Category::factory()->create();
            $child  = Category::factory()->create(['parent_id' => $parent->id]);

            expect($parent->canBeParent($child))->toBeFalse();
        });

        it('detects ancestor relationship', function () {
            $grandparent = Category::factory()->create();
            $parent      = Category::factory()->create(['parent_id' => $grandparent->id]);
            $child       = Category::factory()->create(['parent_id' => $parent->id]);

            expect($grandparent->isAncestorOf($child))->toBeTrue()
                ->and($parent->isAncestorOf($child))->toBeTrue()
                ->and($child->isAncestorOf($grandparent))->toBeFalse();
        });
    });

    describe('ancestors and breadcrumb', function () {
        it('returns empty collection for root category ancestors', function () {
            $category = Category::factory()->create();

            expect($category->getAncestors())->toBeEmpty();
        });

        it('returns all ancestors in correct order', function () {
            $grandparent = Category::factory()->create(['name' => 'Grandparent']);
            $parent      = Category::factory()->create(['name' => 'Parent', 'parent_id' => $grandparent->id]);
            $child       = Category::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

            $ancestors = $child->getAncestors();

            expect($ancestors)->toHaveCount(2)
                ->and($ancestors->first()->name)->toBe('Grandparent')
                ->and($ancestors->last()->name)->toBe('Parent');
        });

        it('returns breadcrumb including self', function () {
            $grandparent = Category::factory()->create(['name' => 'Grandparent']);
            $parent      = Category::factory()->create(['name' => 'Parent', 'parent_id' => $grandparent->id]);
            $child       = Category::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

            $breadcrumb = $child->getBreadcrumb();

            expect($breadcrumb)->toHaveCount(3)
                ->and($breadcrumb->first()->name)->toBe('Grandparent')
                ->and($breadcrumb->last()->name)->toBe('Child');
        });
    });
});
