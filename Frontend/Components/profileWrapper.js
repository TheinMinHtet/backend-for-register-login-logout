class ProfileWrapper extends HTMLElement {
    constructor() {
        super();

        // Call the render method to initialize the component
        this.render();

        // Fetch user profile data from the server
        this.fetchUserProfile();
    }

    render() {
        // Render the initial HTML structure
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

            // Update child components with the fetched user data
            this.updateChildComponents(userData);
        } catch (error) {
            console.error("Error fetching user profile:", error);
        }
    }

    updateChildComponents(userData) {
        // Update <profile-part> component
        const profilePart = this.querySelector("profile-part");
        if (profilePart) {
            profilePart.setAttribute("data", JSON.stringify(userData));
        }

        // Update <para-part> component
        const paraPart = this.querySelector("para-part");
        if (paraPart) {
            paraPart.setAttribute("data", JSON.stringify(userData));
        }

        // Update <bento-part> component
        const bentoPart = this.querySelector("bento-part");
        if (bentoPart) {
            bentoPart.setAttribute("data", JSON.stringify(userData));
        }

        // Update <image-slider> component
        const imageSlider = this.querySelector("image-slider");
        if (imageSlider && userData.skills) {
            imageSlider.setAttribute("skills", JSON.stringify(userData.skills));
        }
    }
}

customElements.define("profile-wrapper", ProfileWrapper);