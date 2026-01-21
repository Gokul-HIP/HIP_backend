function getScrollbarWidth() {
    const outer = document.createElement('div');
    outer.style.visibility = 'hidden';
    outer.style.overflow = 'scroll';
    outer.style.msOverflowStyle = 'scrollbar';
    document.body.appendChild(outer);
    const inner = document.createElement('div');
    outer.appendChild(inner);
    const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
    outer.parentNode.removeChild(outer);
    return scrollbarWidth;
}

document.addEventListener("flux:modal-open", () => {
    const scrollbarWidth = getScrollbarWidth();
    document.body.classList.add("flux-modal-open");

    const bodyOverflow = document.body.style.overflow;
    document.body.dataset.originalOverflow = bodyOverflow || '';

    document.body.style.overflow = 'hidden';
    if (scrollbarWidth > 0) {
        document.body.style.paddingRight = scrollbarWidth + 'px';
    }

    const scrollableElements = document.querySelectorAll('.flex-1, main, .overflow-y-auto');
    scrollableElements.forEach(el => {
        const originalOverflow = el.style.overflow;
        el.dataset.originalOverflow = originalOverflow || '';
        el.style.overflow = 'hidden';
        if (scrollbarWidth > 0) {
            el.style.paddingRight = scrollbarWidth + 'px';
        }
    });
});

document.addEventListener("flux:modal-close", () => {
    document.body.classList.remove("flux-modal-open");

    const bodyOriginalOverflow = document.body.dataset.originalOverflow || '';
    document.body.style.overflow = bodyOriginalOverflow;
    document.body.style.paddingRight = '';
    delete document.body.dataset.originalOverflow;

    const scrollableElements = document.querySelectorAll('.flex-1, main, .overflow-y-auto');
    scrollableElements.forEach(el => {
        const originalOverflow = el.dataset.originalOverflow || '';
        el.style.overflow = originalOverflow;
        el.style.paddingRight = '';
        delete el.dataset.originalOverflow;
    });
});

function toggleFilter(id) {
    document.querySelectorAll('.filter-dropdown').forEach(d => {
        if (d.id !== id) d.classList.add('hidden');
    });
    document.getElementById(id).classList.toggle('hidden');
}

function selectFilter(btn, id) {
    const wrapper = btn.closest('.relative');
    const label = wrapper.querySelector('.filter-label');

    if (label) {
        label.textContent = btn.textContent.trim();
    }

    document.getElementById(id).classList.add('hidden');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest(".filter-btn") && !e.target.closest(".filter-dropdown")) {
        document.querySelectorAll(".filter-dropdown").forEach(d => d.classList.add("hidden"));
    }
});

function toggleActionMenu(event, id) {
    event.stopPropagation();

    document.querySelectorAll(".action-menu").forEach(m => {
        if (m.id !== id) m.classList.add("hidden");
    });

    const menu = document.getElementById(id);
    const btn  = event.target.closest(".action-btn");
    const rect = btn.getBoundingClientRect();

    menu.classList.toggle("hidden");
    if (menu.classList.contains("hidden")) return;

    // Measure menu height safely
    menu.style.visibility = "hidden";
    menu.style.display = "block";
    const menuHeight = menu.offsetHeight;
    menu.style.display = "";
    menu.style.visibility = "";

    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    const VIEWPORT_PADDING = 12;

    let top;

    // Decide direction
    if (spaceBelow < menuHeight && spaceAbove > menuHeight) {
        top = rect.top - menuHeight - 8;
    } else {
        top = rect.bottom + 8; 
    }

    top = Math.max(
        VIEWPORT_PADDING,
        Math.min(
            top,
            window.innerHeight - menuHeight - VIEWPORT_PADDING
        )
    );

    // Position
    menu.style.top  = `${top}px`;
    menu.style.left = `${rect.left - menu.offsetWidth + rect.width}px`;
}

// Close on outside click
document.addEventListener("click", () => {
    document.querySelectorAll(".action-menu")
        .forEach(m => m.classList.add("hidden"));
});


function confirmDelete(id){
    if(confirm("Delete this organization?")){
        window.location.href="/admin/organizations/"+id+"/delete";
    }
}

function initIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeAllActionMenus() {
    document.querySelectorAll('.action-menu').forEach(menu => {
        menu.classList.add('hidden');
    });
}

initIcons();

window.addEventListener('scroll', closeAllActionMenus, true);

document.addEventListener('livewire:navigated', initIcons);

document.addEventListener('click', closeAllActionMenus);

window.addEventListener('icons-need-refresh', initIcons);