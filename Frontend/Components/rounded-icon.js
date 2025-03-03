class roundedIcon extends HTMLElement {
    constructor() {
        super()
        this.isActive = false;
        this.borderElement = null;



        const shadow = this.attachShadow({ mode: 'open' });
        const notiNo = this.getAttribute('notiNO') || "";
        const hasList = this.getAttribute('hasList') || [];
        let setup = ``;

        if (notiNo !== "") {
            setup = `<div class="w-5 h-5 rounded-full flex justify-center items-center absolute -top-[10%] left-[60%] bg-[#ff4647]">${notiNo}</div>`

        }

        // Create a link to Tailwind CSS
        const linkElem = document.createElement('link');
        linkElem.setAttribute('rel', 'stylesheet');
        linkElem.setAttribute('href', '../output.css');

        // Create the container with Tailwind classes
        const container = document.createElement('div');
        container.setAttribute('class', `p-2 bg-[#F1F5F9] rounded-full hover:cursor-pointer shadow-[0px_4px_4px_rgba(0,_0,_0,_0.25),_0px_4px_4px_#C4D3E0,_5px_-3px_4px_#C4D3E0] relative ${(hasList.length > 0) ? '' : 'active:scale-90'} transition-all hover:scale-[1.01] duration-150 hover:shadow-[0px_6px_10px_rgba(0,_0,_0,_0.25),_0px_6px_8px_#C4D3E0,_8px_-4px_6px_#C4D3E0] 
w duration-300`);
        container.innerHTML = setup + `
        
        <slot></slot>
        `;


        // Append the Tailwind stylesheet and content to shadow DOM
        shadow.appendChild(linkElem);
        shadow.appendChild(container);

        if (hasList.length > 0) {
            this.addEventListener('click', this.toggleBorder);

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
        event.stopPropagation();

        const container = this.shadowRoot.querySelector('.relative'); // Access from shadow DOM

        if (!container) {
            console.error("Element with class 'relative' not found.");
            return; // Stop execution to prevent error
        }

        if (!this.isActive) {
            this.borderElement = document.createElement('div');
            this.borderElement.className = 'absolute -bottom-2 left-1/2 -translate-x-1/2 border-2 border-[#f1f5f9] rounded-xl opacity-0 transition-all duration-300 w-[100px] py-3 bg-[#F1F5F9]';
            this.borderElement.style.boxShadow = '8px 8px 16px #C9D9E8, -8px -8px 16px #FFFFFF';
            this.borderElement.innerHTML = `
                <p class="w-full text-[12px] hover:bg-[#dee1e5] p-1" id="skill">Add skill</p>
                <p class="w-full text-[12px] hover:bg-[#dee1e5] p-1" id="memory">Add memory</p>
            `;
            container.appendChild(this.borderElement); // Append safely

            requestAnimationFrame(() => {
                if (this.borderElement) {
                    this.borderElement.style.opacity = '1';
                    this.borderElement.style.transform = 'translateY(130%) scale(1.2)';
                }
            });

            const addSkill = this.borderElement.querySelector('#skill');
            const addMemory = this.borderElement.querySelector('#memory');

            addSkill.addEventListener('click', () => {
                localStorage.removeItem("skillDatafg");
                localStorage.removeItem("skillIdfg");
                window.location.href = '../Skill/index.html'; // Navigate to view profile page
            });

            addMemory.addEventListener('click', () => {
                localStorage.removeItem("memoryDatafg");
                localStorage.removeItem("memoryIdfg");
                window.location.href = '../Memory/index.html'; // Navigate to edit profile page
            });


        } else {
            this.closeMenu();
        }
        this.isActive = !this.isActive;
    }



}

customElements.define("rounded-icon", roundedIcon);


