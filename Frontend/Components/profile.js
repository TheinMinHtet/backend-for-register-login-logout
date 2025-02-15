class profile extends HTMLElement {
    constructor() {
        super();
        this.isActive = false;
        this.borderElement = null;
        const size = this.getAttribute("size") || "";
        const clickable = this.getAttribute("clickable") || "yes";
        this.innerHTML = `
        <div class="relative" >
            <div style="width: ${size}; height: ${size}" class="box-border  bg-[url('./image/profile.jfif')] border-4 border-[#F1F5F9] shadow-[0px_4px_4px_rgba(0,_0,_0,_0.25),_0px_4px_4px_#C4D3E0,_5px_-3px_4px_#C4D3E0] rounded-full bg-cover bg-center ${(clickable !== "no") ? "cursor-pointer" : "" } hover:shadow-[0px_6px_10px_rgba(0,_0,_0,_0.25),_0px_6px_8px_#C4D3E0,_8px_-4px_6px_#C4D3E0] 
    transition-all  duration-300">
            </div>
        </div>
        `;

        if(clickable !== "no") {
            this.addEventListener('click', this.toggleBorder);
        
        // Add document click listener to handle clicks outside
        document.addEventListener('click', (event) => {
            // Check if click is outside of the component and menu is open
            if (this.isActive && !this.contains(event.target)) {
                this.closeMenu();
            }
        });

        }
           
        
    }

    closeMenu() {
        if (this.borderElement) {
            this.borderElement.style.opacity = '0';
            this.borderElement.style.transform = 'translateY(130%) scale(1)';
            this.isActive = false;
            setTimeout(() => {
                this.borderElement?.remove();
                this.borderElement = null;
            }, 300);
        }
        
    }


    toggleBorder(event) {
        // Prevent the click event from bubbling up to document
        event.stopPropagation();
        
        if (!this.isActive) {
            this.borderElement = document.createElement('div');
            this.borderElement.className = 'absolute -bottom-2 left-1/2 -translate-x-1/2 border-2 border-[#f1f5f9] rounded-xl opacity-0 transition-all duration-300 w-[100px] py-3 bg-[#F1F5F9]';
            this.borderElement.style.boxShadow = '8px 8px 16px #C9D9E8, -8px -8px 16px #FFFFFF';
            this.borderElement.innerHTML = `
            <p class="w-full text-[12px] hover:bg-[#dee1e5] p-1">Edit</p>
            <p class="w-full text-[12px] hover:bg-[#dee1e5] p-1 ">Log out</p>
            `;
            this.querySelector('.relative').appendChild(this.borderElement);
            
            requestAnimationFrame(() => {
                if (this.borderElement) {
                    this.borderElement.style.opacity = '1';
                    this.borderElement.style.transform = 'translateY(130%) scale(1.2)';
                }
            });
        } else {
            this.closeMenu();
            this.isActive = !this.isActive;
        }
        this.isActive = !this.isActive;
    }
}

customElements.define("pro-file", profile);