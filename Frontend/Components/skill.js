class Skill extends HTMLElement {
    constructor() {
        super();
    
        // Define the function correctly
        this.limitWords = (text, limit) => {
            if (!text) return "";
            const words = text.split(/\s+/); // Ensure words are split properly
            return words.length > limit ? words.slice(0, limit).join(" ") + " ..." : text;
        };
    
        this.getRandomColor = () => {
            const colors = ['#BEFBFF', '#C8FFBE', '#FFF7BE', '#FFBEC7', '#BEC7FF'];
            return colors[Math.floor(Math.random() * colors.length)];
        };
    
        const description = this.getAttribute("description") || "";
        const title = this.getAttribute("title") || "";
        const color = this.getAttribute("color") || "#F1F5F9";
        const border = this.getAttribute("border") || "8px 8px 16px #C9D9E8, -8px -8px 16px #FFFFFF";
        const days = this.getAttribute("days") || "";
        const taught_count = this.getAttribute("taught_count") || "";
        const tags = JSON.parse(this.getAttribute("tags") || "[]");
        const imgSrc = this.getAttribute("img-src") || "";
    
        let groupedTextHTML = tags.map(tag => `
            <p style="background-color: ${this.getRandomColor()};" 
                class="font-normal text-xl leading-[23px] px-[10px] py-[6px] min-w-[74px]  
                text-center rounded-[28px]">
                ${tag.tag}
            </p>
        `).join("");
    
        // Limit the words for title and description **before** inserting them into innerHTML
        const limitedTitle = this.limitWords(title, 5);
        const limitedDescription = this.limitWords(description, 30);

        console.log("dd",limitedTitle)
    
        this.innerHTML = `  
        <div class="py-8 px-6" 
            style="display: flex; flex-direction: column; align-items: center;
                padding: 24px; gap: 17px; width: 496px; background: ${color};
                box-shadow: ${border}; border-radius: 24px; min-height: 390px;">
            <div class="flex flex-row items-center relative mb-2">
                <h1 class="font-normal leading-[38px] text-[32px] text-[#F1F5F9] 
                    bg-[#2F2F2F] rounded-full ps-[60px] w-[330px] py-7">
                    ${limitedTitle}
                </h1>
                <pro-file size="90px" class="absolute left-[72%]" navi="false" img-src=${imgSrc}></pro-file>
            </div>
            <div>
                <p class="mb-4 font-normal text-xl leading-[23px] text-[#2F2F2F] min-h-[120px]">
                    ${limitedDescription}
                </p>
            </div>
            <div class="py-2 flex flex-row gap-2 w-full">${groupedTextHTML}</div>
            <div class="flex flex-row justify-between items-center w-full">
                <p class="font-normal text-lg leading-[23px] opacity-20 -mt-4">${days} days</p>
                <p class="font-normal text-lg leading-[23px] -mt-4">
                    <span class="opacity-20">taught count: </span>
                    <span class="font-bold">${taught_count}</span>
                </p>
            </div>
        </div>`;
    }
    
}

// Define the custom element
customElements.define("sk-ill", Skill);


/* Music */




// font-weight: 400;
// font-size: 20px;
// line-height: 23px;
// display: flex;
// align-items: center;
// text-align: center;

// color: #2F2F2F;


// /* Inside auto layout */
// flex: none;
// order: 0;
// flex-grow: 0;
