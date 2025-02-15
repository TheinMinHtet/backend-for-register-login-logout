class SkillLearn extends HTMLElement {
    static get observedAttributes() {
        return ["number"];
    }

    constructor() {
        super();
        this.point = 120;
        this.skillNumber = this.getAttribute("number") || "0"; // Store in instance variable
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === "number" && oldValue !== newValue) {
            this.skillNumber = newValue;
            this.render();
        }
    }

    render() {
        this.innerHTML = `
        
            <div class="flex flex-col font-normal leading-[38px] text-[32px] text-[#f1f5f9]">
                <h2>Skills</h2>
                <h2>Learned</h2>
            </div>
            <div>
                <h2 style="border: 3px solid #CCF6D6;box-shadow: 7px 3px 4px #1E1E1E, -3px -3px 11px rgba(149, 147, 147, 0.25);" class="p-4 rounded-full bg-[#2F2F2F] border-[3px] font-normal text-[64px] leading-[75px] text-[#F1F5F9]">${this.skillNumber}</h2>  <!-- Use instance variable -->
            </div>
       
        `;
    }
}

customElements.define("skill-learn", SkillLearn);

/* 23 */

// width: 65px;
// height: 39px;

// font-family: 'Roboto Condensed';
// font-style: normal;
// font-weight: 400;
// font-size: 64px;
// line-height: 75px;
// display: flex;
// align-items: center;

// color: #F1F5F9;

// text-shadow: 0px 4px 4px rgba(0, 0, 0, 0.25);

// /* Inside auto layout */
// flex: none;
// order: 0;
// flex-grow: 0;

