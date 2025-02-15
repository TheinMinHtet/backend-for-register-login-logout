class SortButton extends HTMLElement {
    constructor() {
        super();
        this.value = localStorage.getItem("sortValue") || "title-asc";
        this.isOpen = false;
        this.options = [
            { value: "title-asc", label: "Title: A to Z" },
            { value: "title-desc", label: "Title: Z to A" },
            { value: "tags-asc", label: "Tags: Ascending" },
            { value: "tags-desc", label: "Tags: Descending" }
        ];

        // Bind event handlers
        this.handleClickOutside = this.handleClickOutside.bind(this);
    }

    connectedCallback() {
        this.render();
        this.attachEventListeners();
    }

    attachEventListeners() {
        this.querySelector(".dropdown-button").addEventListener("click", (e) => {
            e.stopPropagation();
            this.isOpen = true;
            this.render();
        });

        this.querySelectorAll(".dropdown-option").forEach((option) => {
            option.addEventListener("click", (e) => {
                e.stopPropagation();
                this.value = e.currentTarget.dataset.value;
                localStorage.setItem("sortValue", this.value);
                this.isOpen = false;
                this.render();
                this.dispatchEvent(new CustomEvent("sort", {
                    detail: { value: this.value },
                    bubbles: true,
                }));
            });
        });

        document.addEventListener("click", this.handleClickOutside);
    }

    handleClickOutside(event) {
        if (!this.contains(event.target)) {
            this.isOpen = false;
            this.render();
        }
    }

    getSelectedLabel() {
        return this.options.find((opt) => opt.value === this.value)?.label || this.options[0].label;
    }

    render() {
        this.innerHTML = `
            <div class=" rounded-full shadow-lg min-w-60 overflow-visible relative hover:cursor-pointer">
                <div class="relative">
                    <button class="dropdown-button w-full flex items-center justify-between px-6 py-3 text-lg bg-white rounded-full border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-all hover:cursor-pointer">
                        <span class="flex items-center">
                            <span class="text-gray-400 font-thin mr-2">Sort by:</span>
                            <span class="font-medium">${this.getSelectedLabel()}</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-down-circle transition-transform ${this.isOpen ? 'rotate-180' : ''} ms-4">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="8 12 12 16 16 12"></polyline>
                            <line x1="12" y1="8" x2="12" y2="16"></line>
                        </svg>
                    </button>

                    ${this.isOpen ? `
                        <div class="absolute z-50 w-full mt-2 bg-white rounded-lg shadow-lg border border-gray-200 py-1 transition-opacity opacity-100">
                            ${this.options.map(option => `
                                <div class="dropdown-option px-6 py-2 hover:bg-[#D3E4FD] cursor-pointer transition-all rounded-md ${this.value === option.value ? 'bg-[#D3E4FD]' : ''}"
                                     data-value="${option.value}">
                                    ${option.label}
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        this.attachEventListeners();
    }

    disconnectedCallback() {
        document.removeEventListener("click", this.handleClickOutside);
    }
}

customElements.define("sort-button", SortButton);
