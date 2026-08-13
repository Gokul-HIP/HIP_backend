@props([
    'embedUrl',
    'mode' => 'template',
    'readOnly' => false,
    'initialPayload' => [],
    'saveMethod' => 'saveFromBuilder',
    'publishMethod' => 'publishFromBuilder',
])

{{--
  Seed the builder with plain JSON (not Alpine reactive state).
  postMessage uses the structured clone algorithm; Alpine Proxies often
  arrive in the iframe with an empty definition → Start-only canvas on Edit.
--}}
@php
    $seedJson = json_encode($initialPayload ?? new stdClass(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
@endphp

<div
    class="automation-builder-frame"
    wire:ignore
    x-data="{
        mode: @js($mode),
        readOnly: @js((bool) $readOnly),
        saveMethod: @js($saveMethod),
        publishMethod: @js($publishMethod),
        ready: false,
        initPosted: false,
        iframeOrigin() {
            const src = this.$refs.frame?.src;
            if (!src) return null;
            try {
                return new URL(src).origin;
            } catch (e) {
                console.error('[hip-builder-frame] Failed to parse iframe origin', e, src);
                return null;
            }
        },
        plainPayload() {
            const el = this.$refs.seed;
            if (!el) return {};
            try {
                return JSON.parse(el.textContent || '{}');
            } catch (e) {
                console.error('[hip-builder-frame] Failed to parse seed JSON', e);
                return {};
            }
        },
        init() {
            window.addEventListener('message', (event) => this.onMessage(event));
        },
        onFrameLoad() {
            this.ready = true;
            this.postInit();
            // One retry if the child's listener was not ready yet.
            setTimeout(() => this.postInit({ force: true }), 400);
        },
        postInit(opts = {}) {
            const frame = this.$refs.frame;
            if (!frame || !frame.contentWindow) return;
            const targetOrigin = this.iframeOrigin();
            if (!targetOrigin) {
                console.error('[hip-builder-frame] Cannot send builder-init: missing iframe origin');
                return;
            }
            if (this.initPosted && !opts.force) return;
            this.initPosted = true;
            const payload = this.plainPayload();
            const template = this.mode === 'template' ? payload : null;
            const workflow = this.mode === 'workflow' ? payload : null;
            frame.contentWindow.postMessage({
                type: 'hip:builder-init',
                mode: this.mode,
                template,
                workflow,
                readOnly: this.readOnly,
                payload,
            }, targetOrigin);
        },
        onMessage(event) {
            const data = event.data || {};
            if (!data.type || !String(data.type).startsWith('hip:')) return;

            if (data.type === 'hip:builder-ready') {
                const targetOrigin = this.iframeOrigin();
                if (!targetOrigin) return;
                if ((event.origin || null) !== targetOrigin) {
                    console.warn('[hip-builder-frame] Ignoring builder-ready due to origin mismatch', {
                        expected: targetOrigin,
                        actual: event.origin || null,
                    });
                    return;
                }
                // Child is listening — push seed (allow even if load already posted).
                this.postInit({ force: true });
                return;
            }
            if (data.type === 'hip:builder-save' && !this.readOnly) {
                $wire[this.saveMethod](data.payload || {});
                return;
            }
            if (data.type === 'hip:builder-publish' && this.mode === 'workflow' && !this.readOnly) {
                $wire[this.publishMethod](data.payload || {});
            }
        }
    }"
>
    <script type="application/json" x-ref="seed">{!! $seedJson !!}</script>

    <style>
        .automation-builder-frame {
            display: flex;
            flex-direction: column;
            min-height: 75vh;
        }
        .automation-builder-frame iframe {
            width: 100%;
            flex: 1;
            min-height: 75vh;
            height: 75vh;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
        }
        .automation-builder-hint {
            font-size: 0.8rem;
            color: #64748b;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
        }
        .automation-builder-hint code {
            font-size: 0.75rem;
            background: #e2e8f0;
            padding: 0.1rem 0.35rem;
            border-radius: 4px;
        }
    </style>

    @if (blank(config('automation.ui_url')))
        <div class="automation-builder-hint">
            Set <code>AUTOMATION_UI_URL</code> in <code>.env</code> to your React Flow builder
            (hip-automation). The admin iframe loads <code>/embed/workflow-builder</code>
            (no frontend login); Livewire saves via the PHP service layer.
        </div>
    @else
        <div class="automation-builder-hint" x-show="!ready">Loading React Flow builder…</div>
        <iframe
            x-ref="frame"
            src="{{ $embedUrl }}"
            title="Workflow Builder"
            allow="clipboard-read; clipboard-write"
            @load="onFrameLoad()"
        ></iframe>
    @endif
</div>
