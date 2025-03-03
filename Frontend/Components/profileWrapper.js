class ProfileWrapper extends HTMLElement {
    constructor() {
        super();

        this.forMemories;

        // Call the render method to initialize the component
        this.render();

        // Fetch user profile data from the server
        this.fetchUserProfile();

        
    }

    render() {
        // Render the initial HTML structure
        this.innerHTML = `
        <style>
        body {
            font-family: 'Roboto Condensed', sans-serif;
            background-color: #F5F5F5;
        }
        
        .frame {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px;
            gap: 17px;
            width: 852px;
            height: 615px;
            background: #F1F5F9;
            box-shadow: 8px 8px 16px #C9D9E8, -8px -8px 16px #FFFFFF;
            border-radius: 24px;
        }

        .image-container {
            width: 804px;
            height: 498px;
            background: url('../Login/cool-background.png') no-repeat center/cover;
            border: 3px solid #F1F5F9;
            border-radius: 16px;
        }

        .upload-box {
            width: 90%;
            height: 5000px;
            top: -30px;
            border: 2px dashed #0099ff;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            background-color: #eef6ff; /* Light blue background */
            padding: 10px; /* Padding inside the rectangle */
        }

        .upload-box img {
            width: calc(100% - 20px); /* Image stays inside padding */
            height: calc(100% - 20px);
            object-fit: cover;
            border-radius: 8px;
            display: none;
        }
        #plusIcon {
            font-size: 30px;
            color: #007BFF;
        }

        .text-box {
            align-items: center;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            gap: 10px;
            
            background: #D3E8FB;
            border-radius: 1000px;
            flex: 1;
            
            width:80%;

        }

        .styled-input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 20px;
            width: 100%;
            padding: 10px;
        }

        .styled-input::placeholder {
            color: #666;
        }

        .text-box p {
            margin: 0 auto;
            width: 732px;
            font-weight: 400;
            font-size: 20px;
            line-height: 23px;
            color: #2F2F2F;
            text-align: center;
        }

        .profile-box {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            padding: 4px;
            gap: 10px;
            width: 52px;
            height: 49px;
            background: #2F2F2F;
            border-radius: 28px;
           
        }

        .profile-box .profile-pic {
            width: 44px;
            height: 41px;
            background: url('../image/profile.jfif') no-repeat center/cover;
            border: 1px solid #F1F5F9;
            border-radius: 50%;
        }
        
        .skill-box {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            width: 112px;
            height: 69px;
            background: #CBF5D7;
            border-radius: 0px 1000px 1000px 0px;
        }

        .skill-box p {
            font-weight: 400;
            font-size: 20px;
            line-height: 23px;
            color: #2F2F2F;
            text-align: center;
        }
    </style>
            <div class="pt-12">
                <profile-part></profile-part>
                <para-part></para-part>
                <bento-part></bento-part>
                <div class="ms-[-6%] w-[112%] bg-[#D3E8FB] pb-[72px] mt-14">
                    <h1 class="font-normal text-8xl leading-[112px] text-[#2F2F2F] pt-8 mb-16 ps-[5%]">Skills</h1>
                    <image-slider></image-slider>
                </div>
                <h1 class="font-normal text-8xl leading-[112px] text-[#2F2F2F] mb-16">Memories</h1>
                <div class="min-h-[100vh] pt-[140px] flex flex-col w-full items-center gap-17" id="wrapper">
                <div class="frame">
            <div class="image-container"></div>
            <div class="w-full flex items-center gap-6">
                <div class="text-box">
                    <p>Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum</p>
                    <div class="profile-box flex-1">
                        <div class="profile-pic"></div>
                    </div>
                </div>
                <div class="skill-box">
                    <p>Skill</p>
                </div>
            </div>
            
        </div>
            </div>
    
        `;
    }

    async fetchUserProfile() {
        try {
            // Make a GET request to the user profile API
            let id = localStorage.getItem("userIdfg")

            let url;
            if(id) {
                url = "http://localhost/skillSwap/skill-swap/user_profile.php/"+id;
                localStorage.removeItem("userIdfg");
            } else {
                url = "http://localhost/skillSwap/skill-swap/user_profile.php";

            }

            const response = await fetch(url, {
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
            this.forMemories = userData;
            this.fetchMemories();

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
            localStorage.setItem("profileImg", userData.profile_img);
            localStorage.setItem("user_id", userData.user_id);
        }
    }

    fetchMemories() {
        const token = localStorage.getItem("JWT");
        if (!token) {
            console.error("No JWT token found in localStorage.");
            return;
        }

        try {
            

            

            const data = this.forMemories;
            console.log("Fetched memories:", data);

            if (!data.memories || data.memories.length === 0) {
                console.warn("No memories found in response.");
                return;
            }

            const container = this.querySelector("#wrapper");

            // Clear existing content (if needed)
            container.innerHTML = '';

            data.memories.forEach(memory => {
                // Create frame element
                const frame = document.createElement("div");
                frame.classList.add("frame");
                frame.classList.add("hover:cursor-pointer");

                frame.addEventListener("click", (event) => {
                    console.log("Frame clicked!");
                    event.stopPropagation();
                
                    localStorage.setItem("memoryIdfg", memory.memory_id);
                    let datar = {
                        description: memory.description,
                        img_name: memory.img_name,
                        user_id: localStorage.getItem("authUser")
                    };
                    localStorage.setItem("memoryDatafg", JSON.stringify(datar));
            
                    // Debug: Log skill data set in localStorage
                    console.log("skillData set in localStorage:", data);
                    
            
                    // Navigate to the skill page
                    window.location.href = "../Memory/index.html";
            
            });

                // Image container
                const imageContainer = document.createElement("div");
                imageContainer.classList.add("image-container");
                imageContainer.style.backgroundImage = `url('../../${memory.img_name}')`;

                // Text and profile box container
                const textContainer = document.createElement("div");
                textContainer.classList.add("w-full", "flex", "items-center", "gap-6");

                // Text box
                const textBox = document.createElement("div");
                textBox.classList.add("text-box");

                const description = document.createElement("p");
                description.textContent = memory.description;

                const profileBox = document.createElement("div");
                profileBox.classList.add("profile-box", "flex-1");

                const profilePic = document.createElement("div");
                profilePic.classList.add("profile-pic");
                profilePic.classList.add("hover:cursor-pointer")
    profilePic.style.backgroundImage = `url('../../${data.profile_img}')`;

    profilePic.addEventListener("click", (event) => {
    // Prevent the event from propagating further
    event.stopPropagation();

    // Do something when the profile picture is clicked
    localStorage.setItem("userIdfg", user.user_id);

        // Navigate to the profile page
        window.location.href = "../Profile/index.html";

});

                // Append profile pic to profile box
                profileBox.appendChild(profilePic);

                // Append text and profile to text box
                textBox.appendChild(description);
                textBox.appendChild(profileBox);

                // Append elements to textContainer
                textContainer.appendChild(textBox);

                // Append imageContainer and textContainer to frame
                frame.appendChild(imageContainer);
                frame.appendChild(textContainer);

                if (memory.name) {
                    const skillBox = document.createElement("div");
                    skillBox.classList.add("skill-box");
                    textContainer.appendChild(skillBox);

                    const text = document.createElement("p");
                    text.textContent = memory.name;
                    skillBox.appendChild(text);

                    // Set border radius properties with !important
                    textBox.style.setProperty("border-top-right-radius", "0", "important");
                    textBox.style.setProperty("border-bottom-right-radius", "0", "important");
                }

                // Append frame to the main container
                container.appendChild(frame);
            });

        } catch (error) {
            console.error("Error fetching memories:", error);
        }
    }
}

customElements.define("profile-wrapper", ProfileWrapper);