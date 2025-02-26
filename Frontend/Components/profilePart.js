class ProfilePart extends HTMLElement {
    static get observedAttributes() {
        return ["data"];
    }

    constructor() {
        super();
        this.point = 0; // Default value
        this.profile_img = ""; // Default value
        this.render();
        this.status = "";
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
                console.log("Parsed data in ProfilePart:", parsedData); // Debugging

                // Extract point and profile_img
                if (Array.isArray(parsedData)) {
                    const user = parsedData[0] || {};
                    this.point = user.points || 0;
                    this.profile_img = user.profile_img || "";
                    this.status = user.status || "";
                }
            } catch (error) {
                console.error("Error parsing data attribute in ProfilePart:", error);
            }
        }
    }

    render() {
        this.innerHTML = `
            <div class="w-full flex flex-col gap-[10px] items-center pb-10">
                <pro-file size="200px" clickable="no" img-src="${this.profile_img}" status="${this.status}"></pro-file>
                <div class="flex gap-1 items-center pt-4">
                    <p class="font-normal leading-[75px] text-[#2F2F2F] text-6xl">${this.point}</p>
                    <span class="font-normal text-xs text-[#2F2F2F] leading-[14px]">Points</span>
                </div>
            </div>
        `;
    }
}

customElements.define("profile-part", ProfilePart);