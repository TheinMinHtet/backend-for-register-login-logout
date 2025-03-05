class Profile extends HTMLElement {
    constructor() {
        super();
        this.isActive = false;
        this.borderElement = null;
        this.render();
    }

    render() {
        const size = this.getAttribute("size") || "";
        const clickable = this.getAttribute("clickable") || "yes";
        const imgSrc = this.getAttribute("img-src") || false;
        const status = this.getAttribute("status") || "";
        const navi = this.getAttribute("navi") || "true";

        const img = imgSrc ? `../../${imgSrc}` : "../image/profile.jfif";

        // Determine status color and text
        const statusColor = status === "busy" ? "#FF9800" : "#22C55E";
        const statusText = status === "busy" ? "User is busy" : "User is active";

        this.innerHTML = `
        <div class="relative group">
            <div style="width: ${size}; height: ${size}; background: url(${img});background-size: cover;
    background-repeat: no-repeat;
    background-position: center center;" 
                 class="box-border bg-[url(${img})] border-4 border-[#F1F5F9] shadow-[0px_4px_4px_rgba(0,_0,_0,_0.25),_0px_4px_4px_#C4D3E0,_5px_-3px_4px_#C4D3E0] 
                        rounded-full bg-cover bg-center ${clickable !== "no" ? "cursor-pointer" : ""} 
                        hover:shadow-[0px_6px_10px_rgba(0,_0,_0,_0.25),_0px_6px_8px_#C4D3E0,_8px_-4px_6px_#C4D3E0] 
                        transition-all duration-300">
            </div>
            
            ${
                navi === "true" ? `
                <div class="absolute -bottom-1 -right-1 flex items-center">
                    <div class="size-3 rounded-full animate-pulse w-4 h-4 absolute left-[-42px] top-[-36px]"
                         style="background-color: ${statusColor};
                                box-shadow: 0 0 6px ${statusColor}">
                    </div>
                    <div class="absolute bottom-0 right-6 opacity-0 group-hover:opacity-100 transition-opacity duration-200
                               bg-white/90 px-2 py-1 rounded-full text-xs whitespace-nowrap shadow-lg">
                        ${statusText}
                    </div>
                </div>` : ""
            }
        </div>
        `;

        if (clickable !== "no") {
            console.log("Adding click event listener");
            this.addEventListener('click', this.toggleBorder.bind(this));

            // Add a document click listener to close the menu when clicking outside
            document.addEventListener('click', (event) => {
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
            
            setTimeout(() => {
                this.borderElement?.remove();
                this.borderElement = null; // Clear the reference
            }, 300);
        }
    }

    toggleBorder(event) {
        event.stopPropagation(); // Prevent event bubbling
        console.log("Before toggle:", this.isActive, "hiii");
    
        if (this.isToggling) return; // Prevent double triggers
        this.isToggling = true;
    
        if (this.isActive) {
            console.log("Closing menu");
            this.closeMenu();
            this.isActive = false;
        } else {
            console.log("Opening menu");
            this.borderElement = document.createElement('div');
            this.borderElement.className = 'absolute -bottom-2 left-1/2 -translate-x-1/2 border-2 border-[#f1f5f9] rounded-xl opacity-0 transition-all duration-300 w-[100px] py-3 bg-[#F1F5F9] hover:cursor-pointer';
            this.borderElement.style.boxShadow = '8px 8px 16px #C9D9E8, -8px -8px 16px #FFFFFF';
            this.borderElement.innerHTML = `
            <p class="w-full text-[12px] hover:bg-[#dee1e5] p-1" id="viewProfile">View Profile</p>
            <p class="w-full text-[12px] hover:bg-[#dee1e5] p-1" id="editProfile">Edit Profile</p>
            <p class="w-full text-[12px] hover:bg-[#dee1e5] p-1" id="logout">Log out</p>
            `;
            this.querySelector('.relative').appendChild(this.borderElement);
            
            requestAnimationFrame(() => {
                if (this.borderElement) {
                    this.borderElement.style.opacity = '1';
                    this.borderElement.style.transform = 'translateY(130%) scale(1.2)';
                }
            });

            // Add event listeners to the menu items
            const viewProfile = this.borderElement.querySelector('#viewProfile');
            const editProfile = this.borderElement.querySelector('#editProfile');
            const logout = this.borderElement.querySelector('#logout');

            viewProfile.addEventListener('click', () => {
                let user = localStorage.getItem('authUser');
                localStorage.setItem('userIdfg',user)
                window.location.href = '../Profile/index.html'; // Navigate to view profile page
            });

            editProfile.addEventListener('click', () => {
                localStorage.setItem('isEdit',"ture");
                window.location.href = '../Profile/edit.html'; // Navigate to edit profile page
            });

            logout.addEventListener('click', () => {

                fetch('http://localhost/skillSwap/skill-swap/login_and_logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ logout: true })
                })
                .then(response => response.json()) // Parse the JSON response
                .then(data => {
                    if (data.status === "success") {
                        // Remove JWT token from localStorage
                        localStorage.removeItem('JWT');
                        localStorage.removeItem('proproFile');
                        localStorage.removeItem('newNoti');
                        // Redirect to login page
                        window.location.href = '../Login/login.html';
                    } else {
                        console.error("Logout failed:", data.message);
                    }
                })
                .catch(error => console.error("Error:", error));

                localStorage.removeItem('JWT');
                window.location.href = '../Login/login.html'; // Navigate to edit profile page
            });

            this.isActive = true;
        }
    
        console.log("After toggle:", this.isActive, "hiii");
    
        // Prevent rapid toggling by using a delay
        setTimeout(() => {
            this.isToggling = false;
        }, 300); // Match with transition duration
    }

    static get observedAttributes() {
        return ['img-src', 'status', 'size', 'clickable', 'navi'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue !== newValue) {
            this.innerHTML = ''; // Clear the current content
            this.render(); // Re-render the component
        }
    }
}

customElements.define("pro-file", Profile);