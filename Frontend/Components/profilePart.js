class ProfilePart extends HTMLElement {

    constructor() {
        super();
        this.point = 120;
       
        this.render();
    }

    render() {
        this.innerHTML = `
            <div class="w-full flex flex-col gap-[10px] items-center pb-10">
                <pro-file  size="200px" clickable="no"></pro-file>
                <div class="flex gap-1 items-center pt-4">
                    <p class="font-normal leading-[75px] text-[#2F2F2F] text-6xl">${this.point}</p>
                    <span class="font-normal text-xs text-[#2F2F2F] leading-[14px]">Points</span>
                </div>
                

                
            </div>
        `;
    }
}

customElements.define("profile-part", ProfilePart);



