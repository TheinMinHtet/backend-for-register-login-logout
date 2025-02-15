class Button extends HTMLElement {
    

    constructor() {
        super();
        this.text = this.getAttribute("text") || "";
        this.color = this.getAttribute("color") || "";
        this.border = this.getAttribute("border") || "8px 8px 16px #78BAF5, -8px -8px 16px #B2D7F9";
        this.render();
    }

   
    render() {
        this.innerHTML = `
            <button style="box-shadow: ${this.border}; background-color: ${this.color}" class="p-4 min-w-[150px] rounded-full bg-[#2F2F2F] hover:cursor-pointer">
                ${this.text}
            </button>
        `;
    }
}

customElements.define("but-ton", Button);


/* knob-elevation */

/* Auto layout */
// display: flex;
// flex-direction: row;
// justify-content: center;
// align-items: center;
// padding: 24px;
// gap: 8px;

// width: 153px;
// height: 48px;

// background: #91C4F2;
// box-shadow: 8px 8px 16px #78BAF5, -8px -8px 16px #B2D7F9;
// border-radius: 1e+11px;

// /* Inside auto layout */
// flex: none;
// order: 1;
// align-self: stretch;
// flex-grow: 1;
// z-index: 1;
