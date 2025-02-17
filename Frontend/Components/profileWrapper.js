class ProfileWrapper extends HTMLElement {
    constructor() {
        super();
       
        this.render();
    }

    render() {
        this.innerHTML = `
            <div class="pt-12">
                <profile-part></profile-part>
                <para-part></para-part>
                <bento-part></bento-part>
                <div class="ms-[-6%] w-[112%] bg-[#D3E8FB] pb-[72px] mt-14">
                    <h1 class="font-normal text-8xl leading-[112px] text-[#2F2F2F] pt-8 mb-16 ms-[5%]">Skills</h1>
                    <image-slider></image-slider>
                </div>
                
                
                

                
            </div>
        `;
    }
}

customElements.define("profile-wrapper", ProfileWrapper);

// /* Frame 43 */

// /* Auto layout */
// display: flex;
// flex-direction: column;
// align-items: flex-start;
// padding: 16px 8px;
// gap: 22px;

// width: 496px;
// height: 367px;

// background: #D3E8FB;
// box-shadow: -18px -18px 36px rgba(255, 255, 255, 0.25), 18px 18px 36px rgba(0, 0, 0, 0.25);
// border-radius: 24px;

// /* Inside auto layout */
// flex: none;
// order: 0;
// flex-grow: 0;

