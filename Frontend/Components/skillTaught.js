
class SkillTaught extends HTMLElement {
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
        
            <div class="flex flex-col font-normal leading-[38px] text-[32px] text-[#2F2F2F]">
                <h2>Skills</h2>
                <h2>Taught</h2>
            </div>
            <div>
                <h2 style="border: 3px solid #CCF6D6;box-shadow: 8px 8px 16px #C9D9E8, -8px -8px 16px rgba(255, 255, 255, 0.25);" class="p-4 rounded-full bg-[#CCF6D6] border-[3px] font-normal text-[64px] leading-[75px] text-[#2f2f2f]">${this.skillNumber}</h2>  <!-- Use instance variable -->
            </div>
       
        `;
    }
}

customElements.define("skill-taught", SkillTaught);

/* Frame 33 */

// box-sizing: border-box;

// /* Auto layout */
// display: flex;
// flex-direction: row;
// justify-content: center;
// align-items: center;
// padding: 16px;
// gap: 10px;

// margin: 0 auto;
// width: 108px;
// height: 105px;

// background: #CCF6D6;
// border: 3px solid #CCF6D6;
// box-shadow: 8px 8px 16px #C9D9E8, -8px -8px 16px rgba(255, 255, 255, 0.25);
// border-radius: 9999px;

// /* Inside auto layout */
// flex: none;
// order: 1;
// flex-grow: 0;


