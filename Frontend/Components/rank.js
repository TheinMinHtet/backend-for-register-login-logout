class Rank extends HTMLElement {
    static get observedAttributes() {
        return ["points"];
    }

    constructor() {
        super();
        this.points = 0; // Default value
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === "points") {
            this.points = parseInt(newValue) || 0;
            this.render();
        }
    }

    getRank() {
        if (this.points <= 100) {
            return { rank: "Elite", image: "../image/elite.png" };
        } else if (this.points <= 150) {
            return { rank: "Master", image: "../image/master.png" };
        } else {
            return { rank: "Epic", image: "../image/epic.png" };
        }
    }

    render() {
        const { rank, image } = this.getRank();

        this.innerHTML = `
            <rounded-icon class="mb-8">
                <image src="${image}" class="w-[150px]"/>
            </rounded-icon>
            <div class="relative m-auto">
                <span class="absolute font-normal text-base leading-[19px] text-[#2F2F2F] ms-2 mt-2">Current Rank:</span>
                <h1 class="leading-[112px] text-8xl text-[#2F2F2F]">${rank}</h1>
            </div>
        `;
    }
}

customElements.define("ra-nk", Rank);