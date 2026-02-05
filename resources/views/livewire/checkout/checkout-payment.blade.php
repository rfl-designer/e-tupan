<div>
    <flux:heading size="lg" class="mb-6">
        Forma de Pagamento
    </flux:heading>

    @error('paymentMethod')
        <flux:callout variant="danger" class="mb-6">
            {{ $message }}
        </flux:callout>
    @enderror

    {{-- Payment Method Selection --}}
    <div class="space-y-4">
        @foreach ($this->paymentMethods as $method)
            <div
                wire:key="payment-{{ $method['value'] }}"
                wire:click="selectMethod('{{ $method['value'] }}')"
                class="p-4 border rounded-lg cursor-pointer transition-colors
                    {{ $paymentMethod === $method['value']
                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                        : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' }}"
            >
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        @if ($paymentMethod === $method['value'])
                            <flux:icon name="check-circle" class="size-5 text-primary-600" />
                        @else
                            <div class="size-5 rounded-full border-2 border-zinc-300 dark:border-zinc-600"></div>
                        @endif
                    </div>
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-{{ $method['color'] }}-100 dark:bg-{{ $method['color'] }}-900/30 flex items-center justify-center">
                        <flux:icon name="{{ $method['icon'] }}" class="size-5 text-{{ $method['color'] }}-600 dark:text-{{ $method['color'] }}-400" />
                    </div>
                    <div>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $method['label'] }}
                        </span>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $method['description'] }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Credit Card Form --}}
    @if ($paymentMethod === 'credit_card')
        <div class="mt-6 p-6 bg-zinc-50 dark:bg-zinc-900 rounded-lg space-y-4">
            <flux:heading size="md">Dados do Cartao</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model.live.debounce.300ms="cardData.number"
                        label="Numero do cartao *"
                        type="text"
                        placeholder="0000 0000 0000 0000"
                        maxlength="19"
                        :error="$errors->first('cardData.number')"
                    />
                </div>

                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="cardData.name"
                        label="Nome impresso no cartao *"
                        type="text"
                        placeholder="NOME COMO ESTA NO CARTAO"
                        :error="$errors->first('cardData.name')"
                    />
                </div>

                <div>
                    <flux:input
                        wire:model.live.debounce.300ms="cardData.expiry"
                        label="Validade *"
                        type="text"
                        placeholder="MM/AA"
                        maxlength="5"
                        :error="$errors->first('cardData.expiry')"
                    />
                </div>

                <div>
                    <flux:input
                        wire:model.live.debounce.300ms="cardData.cvv"
                        label="CVV *"
                        type="text"
                        placeholder="123"
                        maxlength="4"
                        :error="$errors->first('cardData.cvv')"
                    />
                </div>

                @if (!empty($this->installmentOptions))
                    <div class="sm:col-span-2">
                        <flux:select
                            wire:model="installments"
                            label="Parcelamento"
                        >
                            @foreach ($this->installmentOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                @endif
            </div>

            <div class="flex items-start gap-2 pt-2 text-sm text-zinc-500 dark:text-zinc-400">
                <flux:icon name="lock-closed" class="size-4 flex-shrink-0 mt-0.5" />
                <p>Seus dados estao seguros. Usamos criptografia SSL para proteger suas informacoes.</p>
            </div>
        </div>
    @endif

    {{-- Pix Info --}}
    @if ($paymentMethod === 'pix')
        <div class="mt-6 p-6 bg-teal-50 dark:bg-teal-900/20 rounded-lg">
            <div class="flex items-start gap-4">
                <flux:icon name="qr-code" class="size-8 text-teal-600 dark:text-teal-400 flex-shrink-0" />
                <div>
                    <flux:heading size="md" class="text-teal-900 dark:text-teal-100">Pix</flux:heading>
                    <p class="mt-1 text-sm text-teal-700 dark:text-teal-300">
                        Apos finalizar o pedido, voce recebera um QR Code para realizar o pagamento.
                        O pedido sera confirmado automaticamente apos a identificacao do pagamento.
                    </p>
                    <ul class="mt-3 text-sm text-teal-600 dark:text-teal-400 space-y-1">
                        <li class="flex items-center gap-2">
                            <flux:icon name="check" class="size-4" />
                            Aprovacao imediata
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon name="check" class="size-4" />
                            Valido por 30 minutos
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon name="check" class="size-4" />
                            Sem taxas adicionais
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Bank Slip Info --}}
    @if ($paymentMethod === 'bank_slip')
        <div class="mt-6 p-6 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
            <div class="flex items-start gap-4">
                <flux:icon name="document-text" class="size-8 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                <div>
                    <flux:heading size="md" class="text-amber-900 dark:text-amber-100">Boleto Bancario</flux:heading>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                        O boleto sera gerado apos a finalizacao do pedido. O pagamento pode ser feito
                        em qualquer banco, casa loterica ou pelo internet banking.
                    </p>
                    <ul class="mt-3 text-sm text-amber-600 dark:text-amber-400 space-y-1">
                        <li class="flex items-center gap-2">
                            <flux:icon name="check" class="size-4" />
                            Vencimento em 3 dias uteis
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon name="check" class="size-4" />
                            Confirmacao em ate 3 dias uteis apos o pagamento
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon name="information-circle" class="size-4" />
                            O prazo de entrega comeca apos a confirmacao
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="pt-6 border-t border-zinc-200 dark:border-zinc-700 mt-6">
        <div class="flex justify-end">
            <flux:button
                wire:click="continueToReview"
                variant="primary"
                wire:loading.attr="disabled"
                :disabled="$paymentMethod === null"
            >
                <span wire:loading.remove wire:target="continueToReview">Revisar pedido</span>
                <span wire:loading wire:target="continueToReview">Processando...</span>
                <flux:icon name="arrow-right" class="size-4 ml-2" wire:loading.remove wire:target="continueToReview" />
            </flux:button>
        </div>
    </div>
</div>
