class ResultsCount extends HTMLElement {
    constructor() {
        super();
        this.count = 0;
    }

    static get observedAttributes() {
        return ['count'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === 'count') {
            this.count = parseInt(newValue);
            this.render();
        }
    }

    connectedCallback() {
        this.render();
    }

    render() {
        this.innerHTML = `
            <div class="text-xl text-[rgba(47,47,47,1)]">
                <span class="font-extrabold">${this.count}</span> Results
            </div>
        `;
    }
}

customElements.define("results-count", ResultsCount);