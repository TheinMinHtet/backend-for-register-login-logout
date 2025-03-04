class navigation extends HTMLElement {
    constructor() {
        super()
        this.fetchUserProfile();
        this.innerHTML = `  
        <div class="bg-[#F1F5F9] flex flex-row w-full px-[5%] py-5 items-center gap-5 fixed z-[100]">  
        <div class="flex flex-row gap-9">
        <a href="../Home/index.html">
            <rounded-icon>
                <svg width="24" height="24" viewBox="0 0 47 46" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M30.3334 43.0668H34.8889C39.9208 43.0668 44 38.9245 44 33.8149V19.2609C44 16.0256 42.3358 13.0255 39.6112 11.3487L28.2223 4.33969C25.3198 2.55344 21.6803 2.55344 18.7778 4.33969L7.3889 11.3487C4.66422 13.0255 3.00008 16.0256 3.00008 19.2609V33.8149C3.00008 38.9245 7.07935 43.0668 12.1112 43.0668H16.6667M30.3334 43.0668V33.8149C30.3334 29.9826 27.2741 26.876 23.5 26.876C19.726 26.876 16.6667 29.9826 16.6667 33.8149V43.0668M30.3334 43.0668H16.6667" stroke="black" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
            </rounded-icon>
        </a>
             
             <rounded-icon hasList="one,two">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </rounded-icon>
        
        </div>
        <search-bar class="flex-1"></search-bar>
        <div class="flex flex-row gap-9">
             <rounded-icon  notiNo="2" class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bell"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </rounded-icon>
            <pro-file  size="40px" navi="false"></pro-file>
                
        
        </div>
           

            
            
        </div>
        `
    }

    async fetchUserProfile() {
        try {
            // Make a GET request to the user profile API
            const response = await fetch("http://localhost/skillSwap/skill-swap/user_profile.php", {
                method: "GET",
                headers: {
                    "Authorization": "Bearer " + localStorage.getItem("JWT"), // Corrected concatenation
                    "Content-Type": "application/json"
                }
            });

            // Check if the response is successful
            if (!response.ok) {
                throw new Error("Failed to fetch user profile");
            }

            // Parse the JSON response
            const data = await response.json();

            // Log the fetched data to the console (for debugging)
            console.log("Fetched user profile:", data);

            // Extract the `user` object from the response
            const userData = data.user;
            localStorage.setItem("authUser",userData.user_id);
            localStorage.setItem("authUserData",JSON.stringify(userData));

            // Update child components with the fetched user data
            this.updateChildComponents(userData);
        } catch (error) {
            console.error("Error fetching user profile:", error);
        }
    }

    updateChildComponents(userData) {

       

       

        // Update <image-slider> component
        const imageSlider = this.querySelector("pro-file");
        if (imageSlider && userData.profile_img) {
            imageSlider.setAttribute("img-src", `${userData.profile_img}`);
            

        }
    }
}

customElements.define("navi-gation",navigation);

// <rounded-icon svg="" data=[] clickable=true></round-icon>
            // <rounded-icon svg="" data=[] clickable=true></round-icon>
            // <search-bar tag=[data]></search-bar>
            // <noti-icon svg="" data=[] clickable=true></round-icon>
            // <profile svg="" data=[] clickable=true></round-icon>