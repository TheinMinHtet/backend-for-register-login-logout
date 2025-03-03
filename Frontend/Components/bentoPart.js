class BentoPart extends HTMLElement {
    static get observedAttributes() {
        return ["data"];
    }

    constructor() {
        super();
        this.number = 20; // Default value for <skill-learn>
        this.number2 = 32; // Default value for <skill-taught>
        this.points = 0; // Default value for <ra-nk>
        this.infoData = []; // Default value for <infor-mation>
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === "data") {
            this.updateData(newValue);
            this.render();
        }
    }

    updateData(data) {
        if (data) {
            try {
                const parsedData = JSON.parse(data);
                console.log("Parsed data in BentoPart:", parsedData); // Debugging

                // Extract points and infoData
                if (parsedData) {
                    const user = parsedData || {};
                    this.points = user.points || 0;
                    this.skill_learnt = user.skill_learnt || 0;
                    this.skill_taught = user.skill_taught || 0;

                    // Format infoData for <infor-mation>
                    this.infoData = [
                        { one: user.city || "N/A", two: "City" },
                        { one: user.region || "N/A", two: "Region" },
                        { one: user.country || "N/A", two: "Country" },
                        { one: user.telegram_username || "N/A", two: "Telegram Account" },
                        { one: user.telegram_phone || "N/A", two: "Phone No." }
                    ];
                }
            } catch (error) {
                console.error("Error parsing data attribute in BentoPart:", error);
            }
        }
    }

    render() {
        this.innerHTML = `
            <div class="w-full grid grid-cols-3 grid-rows-2 gap-x-20 gap-y-14 pb-20 px-[5%]">
                <ra-nk class="bg-[#D3E8FB] row-span-2 rounded-[28px] py-10 flex flex-col items-center justify-between gap-8" points="${this.points}"></ra-nk>
                <skill-learn class="bg-[#2F2F2F] h-[161px] p-8 flex justify-between items-center rounded-[28px]" number=${this.skill_learnt}></skill-learn>
                <infor-mation class="bg-[#D3E8FB] row-span-2 rounded-[28px] px-6 py-10" data='${JSON.stringify(this.infoData)}'></infor-mation>
                <skill-taught class="bg-[#D3E8FB] h-[161px] p-8 flex justify-between items-center rounded-[28px]" number=${this.skill_taught}></skill-taught>
            </div>
        `;
    }
}

customElements.define("bento-part", BentoPart);