class Skill extends HTMLElement {
    constructor() {
        super();

        // Correct function definition
        this.limitWords = (text, limit = 35) => {
            const words = text.split(/\s+/); // Split text by spaces
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
        const taught_count= this.getAttribute("taught_count") || "";

        //`Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut eu mollis tortor, sed posuere elit. Pellentesque sed imperdiet arcu, non interdum libero. Mauris non mi id enim volutpat efficitur. Donec quis eros at nunc maximus tristique. Nunc pretium risus magna, et vehicula leo tincidunt nec. Mauris mollis vehicula ante ac sollicitudin. Nullam non justo at purus accumsan aliquam.

        const tags = JSON.parse(this.getAttribute("tags") || "[]");
        let groupedTextHTML = "";

        for (let i = 0; i < tags.length; i++) {
            groupedTextHTML += `
                
                    <p style="background-color: ${this.getRandomColor()};" class="font-normal text-xl leading-[23px] px-[10px] py-[6px] min-w-[74px]  text-center rounded-[28px]">${tags[i].tag}</p>
                
                
            `;
        }

        this.innerHTML = `  
        <div class="py-8 px-6" 
        style="
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px;
            gap: 17px;
            width: 496px;
            background: ${color};
            box-shadow: ${border};
            border-radius: 24px;
            min-height: 390px;
        ">
            <div class="flex flex-row items-center relative mb-2">
                <h1 class="font-normal leading-[38px] text-[32px] text-[#F1F5F9] bg-[#2F2F2F] rounded-full ps-[60px] w-[330px] py-7">${title}</h1>
                <pro-file  size="90px" class="absolute left-[72%]"></pro-file>
            </div>
            <div>
                
                <p class="mb-4 font-normal text-xl leading-[23px] text-[#2F2F2F] min-h-[120px]">
                ${this.limitWords(description, 30)} 
            </p>
            
            </div>
            
            <div class="py-2 flex flex-row gap-2 w-full">${groupedTextHTML}</div>
            <div class="flex flex-row justify-between items-center w-full">
                <p class="font-normal text-lg leading-[23px] opacity-20 -mt-4">${days} days</p>
            <p class="font-normal text-lg leading-[23px]  -mt-4"><span class="opacity-20">taught count: </span><span class="font-bold">${taught_count}</span> </p>
            
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
