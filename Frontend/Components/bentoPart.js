class BentoPart extends HTMLElement {

    constructor() {
        super();
        this.number = 20;
        this.number2 = 32;
       
       
        this.render();

    }

    render() {
        this.innerHTML = `
            <div class="w-full grid grid-cols-3 grid-rows-2 gap-x-20 gap-y-14 pb-20 px-[5%]">
            <ra-nk class="bg-[#D3E8FB] row-span-2 rounded-[28px] py-10 flex flex-col items-center justify-between gap-8"></ra-nk>
            <skill-learn class="bg-[#2F2F2F] h-[161px] p-8 flex justify-between items-center rounded-[28px]" number=${this.number}></skill-learn>
            <infor-mation class="bg-[#D3E8FB] row-span-2 rounded-[28px] px-6 py-10" data='[{"one": "Yangon", "two": "City"}, {"one": "Yangon", "two": "Region"},{"one": "Myanmar", "two": "Country"},{"one": "@lua_boy", "two": "Telegram_account"},{"one": "22", "two": "Age"},{"one": "09-123456789", "two": "Phone No."}]'></infor-mation>
            <skill-taught class="bg-[#D3E8FB] h-[161px] p-8 flex justify-between items-center rounded-[28px]" number=${this.number2}></skill-taught>

               
                

                
            </div>
        `;
    }
}

customElements.define("bento-part", BentoPart);


/* Frame 30 */

/* Auto layout */
// display: flex;
// flex-direction: column;
// justify-content: center;
// align-items: flex-start;
// padding: 24px;
// gap: 11px;

// margin: 0 auto;
// width: 351px;
// height: 403px;

// background: #D3E8FB;
// border-radius: 28px;

// /* Inside auto layout */
// flex: none;
// order: 2;
// flex-grow: 0;





