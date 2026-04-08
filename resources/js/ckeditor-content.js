function getCkeditor() {
    return window.CKEDITOR || null;
}

function bindEditorInComponent(component) {
    if (!component?.el || !component.$wire) {
        return;
    }

    const CKEDITOR = getCkeditor();
    if (!CKEDITOR) {
        return;
    }

    component.el.querySelectorAll('textarea[data-ck-content-description="1"]').forEach((textarea) => {
        if (textarea.dataset.ckBound === "1") {
            return;
        }

        textarea.dataset.ckBound = "1";
        if (!textarea.id) {
            textarea.id = `content_description_${Math.random().toString(36).slice(2, 10)}`;
        }

        let editor;
        try {
            editor = CKEDITOR.replace(textarea.id, {
                height: 220,
                removeButtons: "PasteFromWord",
            });
        } catch (error) {
            console.error("[CKEditor] init failed:", error);
            textarea.dataset.ckBound = "0";
            textarea.style.visibility = "";
            return;
        }

        const sync = () => {
            component.$wire.set("description", editor.getData(), false);
        };

        editor.on("change", sync);
        editor.on("blur", sync);
        editor.on("instanceReady", () => {
            textarea.style.visibility = "";
        });
        editor.on("error", () => {
            textarea.dataset.ckBound = "0";
            textarea.style.visibility = "";
        });

        const form = textarea.closest("form");
        if (form) {
            form.addEventListener("submit", sync, { capture: true });
        }
    });
}

function scanAllComponents() {
    if (!window.Livewire?.all) {
        return;
    }
    window.Livewire.all().forEach((component) => bindEditorInComponent(component));
}

let hooksRegistered = false;
let retryTimer = null;

function bootEditors() {
    if (!getCkeditor()) {
        if (retryTimer === null) {
            retryTimer = window.setTimeout(() => {
                retryTimer = null;
                bootEditors();
            }, 100);
        }
        return;
    }

    scanAllComponents();

    if (!hooksRegistered && window.Livewire?.hook) {
        hooksRegistered = true;
        Livewire.hook("component.init", ({ component }) => {
            queueMicrotask(() => bindEditorInComponent(component));
        });
    }
}

document.addEventListener("livewire:init", bootEditors);
document.addEventListener("DOMContentLoaded", bootEditors);
