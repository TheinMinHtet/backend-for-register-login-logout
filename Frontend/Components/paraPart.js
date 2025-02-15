class ParaPart extends HTMLElement {

    constructor() {
        super();
        this.point = 120;
       
        this.render();
    }

    render() {
        this.innerHTML = `
            <div class="w-full flex justify-center items-center pt-5 pb-20">
                <p class="w-[500px] text-center text-[20px] font-medium">Lorem ipsum dolor sit amet consectetur. Nisi sit nullam libero massa.Lorem ipsum dolor sit amet consectetur. Nisi sit nullam libero massa.Lorem ipsum dolor sit amet consectetur. Nisi sit nullam libero massa...</p>
                
                
                
            </div>
        `;
    }
}

customElements.define("para-part", ParaPart);



