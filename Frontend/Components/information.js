class Information extends HTMLElement {
    static get observedAttributes() {
        return ["data"];
    }

    constructor() {
        super();
        this.getRandomColor = () => {
            const colors = ['#C8FFBE', '#F1F5F9'];
          return colors[Math.floor(Math.random() * colors.length)];
    
          };
          this.getRandomColor2 = () => {
            const colorss = ['#C8FFBE', '#F1F5F9'];
          return colorss[Math.floor(Math.random() * colorss.length)];

          };
        this.data = JSON.parse(this.getAttribute("data") || "[]"); // Parse JSON attribute
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === "data" && oldValue !== newValue) {
            this.data = JSON.parse(newValue);
            this.render();
        }
    }


    render() {
        this.innerHTML = `
            <div class="flex flex-row flex-wrap gap-3">
                ${this.data.map(item => `
                    <div class="rounded-full px-[10px] py-[5px] shadow-[7px_3px_4px_#1E1E1E,-3px_-3px_11px_rgba(149,147,147,0.25)] font-normal leading-[23px] text-xl flex gap-[10px] bg-[${this.getRandomColor()}] w-fit">
                        ${item.two}
                        <div style="color: ${this.getRandomColor2()}" class="bg-[#2F2F2F] rounded-full min-w-[68px] px-2 py-1 font-normal text-base leading-[19px] text-center flex items-center">
                            ${item.one}
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
}

customElements.define("infor-mation", Information);
