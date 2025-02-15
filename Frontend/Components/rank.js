class Rank extends HTMLElement {
    

    constructor() {
        super();
        
        this.render();
    }

   
    render() {
        this.innerHTML = `
            <rounded-icon class="mb-8">
                <image src="../image/elite.png" class="w-[150px]"/>
            </rounded-icon>
            <div class="relative m-auto">
             
                <span class="absolute font-normal text-base leading-[19px] text-[#2F2F2F] ms-2 mt-2">Current Rank:</span>
                <h1 class="leading-[112px] text-8xl text-[#2F2F2F]" >Epic</h1>
                
            </div>
        `;
    }
}

customElements.define("ra-nk", Rank);


/* Current Rank: */

// width: 148px;
// height: 19px;

// font-family: 'Roboto Condensed';
// font-style: normal;
// font-weight: 400;
// font-size: 16px;
// line-height: 19px;
// /* identical to box height */
// display: flex;
// align-items: center;

// color: #2F2F2F;

// border: 1px solid rgba(0, 0, 0, 0.51);

// /* Inside auto layout */
// flex: none;
// order: 0;
// flex-grow: 0;

