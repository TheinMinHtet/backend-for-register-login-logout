class ProfileForm extends HTMLElement {
    constructor() {
        super();
        this.getRandomColor = () => {
            const colors = ['#BEFBFF', '#C8FFBE', '#FFF7BE', '#FFBEC7', '#BEC7FF'];
            return colors[Math.floor(Math.random() * colors.length)];
        };
        this.availableTags = [
            { id: 1, text: "music", color: this.getRandomColor() },
            { id: 2, text: "law", color: this.getRandomColor() },
            { id: 3, text: "tech", color: this.getRandomColor() },
            { id: 4, text: "sports", color: this.getRandomColor() },
            { id: 5, text: "cooking", color: this.getRandomColor() },
            { id: 6, text: "writing", color: this.getRandomColor() },
            { id: 7, text: "photography", color: this.getRandomColor() },
            { id: 8, text: "design", color: this.getRandomColor() }
        ];
        this.selectedTags = [];
        this.render();
        this.attachEventListeners();
        this.loadCountries(); // Load countries when the component is initialized
    }

    connectedCallback() {
        this.render();
        this.attachEventListeners();
    }

    addTag(tag) {
        if (!this.selectedTags.find(t => t.id === tag.id)) {
            this.selectedTags.push(tag);
            this.availableTags = this.availableTags.filter(t => t.id !== tag.id);
            this.render();
            this.attachEventListeners(); // Reattach event listeners after render
        }
    }

    removeTag(tag) {
        if (!this.availableTags.find(t => t.id === tag.id)) {
            this.availableTags.push(tag);
            this.selectedTags = this.selectedTags.filter(t => t.id !== tag.id);
            this.render();
            this.attachEventListeners(); // Reattach event listeners after render
        }
    }

    handleSubmit(e) {
    e.preventDefault();

    const countrySelect = this.querySelector('.country');
    const selectedCountryIndex = countrySelect.selectedIndex;
    const selectedCountryOption = countrySelect.options[selectedCountryIndex];
    const selectedCountryName = selectedCountryOption.textContent;

    // Get the selected state name
    const stateSelect = this.querySelector('.state');
    const selectedStateIndex = stateSelect.selectedIndex;
    const selectedStateOption = stateSelect.options[selectedStateIndex];
    const selectedStateName = selectedStateOption.textContent;

    // Get the selected city name
    const citySelect = this.querySelector('.city');
    const selectedCityIndex = citySelect.selectedIndex;
    const selectedCityOption = citySelect.options[selectedCityIndex];
    const selectedCityName = selectedCityOption.textContent;

  

    // Initialize Notyf for notifications
    const notyf = new Notyf({
        duration: 5000,
        position: { x: 'right', y: 'top' },
        types: [
            { type: 'success', background: '#4CAF50', icon: false },
            { type: 'error', background: '#FF5252', icon: false }
        ]
    });

    // Get the JWT token from localStorage
    const token = localStorage.getItem("JWT");
    if (!token) {
        notyf.error("No token found. Please log in.");
        return;
    }

    // Get form data
    const userData = JSON.parse(localStorage.getItem("authUserData")) || {};

    const formData = {
        username: this.querySelector('#username').value.trim() || userData.username,    
        password: userData.password,
        description: this.querySelector('#description').value.trim() || userData.bio,
        tags: this.selectedTags,
        country: selectedCountryName.trim(),
        region: selectedStateName.trim(),
        city:selectedCityName.trim(),
        telegramUsername: this.querySelector('#telegram').value.trim() || userData.telegram_phone,
        telegramPhone: this.querySelector('#phone').value.trim() || userData.telegram_phone
    };

    // Get the image file
    const imageInput = this.querySelector('#profile');
    const imageFile = imageInput.files[0];

    // Validation checks
    if (!formData.username) {
        notyf.error("Username is required.");
        return;
    }
    if (!formData.password) {
        notyf.error("Password is required.");
        return;
    }

    


   

    if (!formData.description) {
        notyf.error("Biography is required.");
        return;
    }
    if (!formData.country || formData.country === "Country") {
        notyf.error("Please select a country.");
        return;
    }
    if (!formData.region || formData.region === "Region") {
        notyf.error("Please select a region.");
        return;
    }
    if (!formData.city || formData.city === "City") {
        notyf.error("Please select a city.");
        return;
    }
    if (!formData.telegramUsername) {
        notyf.error("Telegram username is required.");
        return;
    }
    if (!formData.telegramPhone) {
        notyf.error("Phone number is required.");
        return;
    }
    if (!imageFile) {
        notyf.error("Please select a profile image.");
        return;
    }

    // Check for image duplication
    const lastUploadedImageName = localStorage.getItem("lastUploadedImageName");
    if (lastUploadedImageName === imageFile.name) {
        notyf.error("This image has already been uploaded. Please select a different image.");
        return;
    }

    // Create FormData for the image upload
    const imageFormData = new FormData();
    imageFormData.append('profile_image', imageFile);

    // Log the first API request
    console.log("First API Request (Image Upload):", {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'multipart/form-data'
        },
        body: imageFormData
    });

    // First API call: Upload the image
    fetch('http://localhost/skillSwap/skill-swap/user_profile.php', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'multipart/form-data'
        },
        body: imageFormData
    })
    .then(response => response.json())
    .then(data => {
        console.log("First API Response (Image Upload):", data);

        if (data.status === "success") {
            notyf.success("Image uploaded successfully!");

            // Store the latest successful upload name in localStorage
            localStorage.setItem("lastUploadedImageName", imageFile.name);

            // Create FormData for the second API call
            const profileFormData = new FormData();
            profileFormData.append('city', formData.city);
            profileFormData.append('region', formData.region);
            profileFormData.append('country', formData.country);
            profileFormData.append('bio', formData.description);
            profileFormData.append('status', "Active");
            profileFormData.append('username', formData.username);
            profileFormData.append('password', formData.password);
            profileFormData.append('telegram_username', formData.telegramUsername);
            profileFormData.append('telegram_phone', formData.telegramPhone);

            // Log the second API request
            console.log("Second API Request (Profile Update):", {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'multipart/form-data'
                },
                body: profileFormData
            });

            // Second API call: Update profile data
            fetch('http://localhost/skillSwap/skill-swap/user_profile.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'multipart/form-data'
                },
                body: profileFormData
            })
            .then(response => response.json())
            .then(data => {
                console.log("Second API Response (Profile Update):", data);

                if (data.status === "success") {
                    notyf.success("Profile updated successfully!");
                } else {
                    notyf.error("Failed to update profile.");
                    console.error('Failed to update profile:', data);
                }
            })
            .catch(error => {
                // notyf.error("Error updating profile.");
                console.error('Error updating profile:', error);
            });
        } else {
            notyf.error("Failed to upload image.");
            console.error('Failed to upload image:', data);
        }
    })
    .catch(error => {
        notyf.error("Error uploading image.");
        console.error('Error uploading image:', error);
    });

    // Reset form without reloading
    this.querySelector('form').reset();
    window.location.href = '../Profile/index.html'
}


    attachEventListeners() {
        const form = this.querySelector('form');
        form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Bind the submit button
        const submitButton = this.querySelector('but-ton[text="Submit"]');
        if (submitButton) {
            submitButton.addEventListener('click', (e) => {
                e.preventDefault(); // Prevent default button behavior
                this.handleSubmit(e); // Manually call handleSubmit
            });
        }

        // Attach tag click handlers
        this.querySelectorAll('.available-tag').forEach(tag => {
            tag.addEventListener('click', (e) => {
                const tagId = parseInt(e.target.dataset.id);
                const tag = this.availableTags.find(t => t.id === tagId);
                if (tag) this.addTag(tag);
            });
        });

        this.querySelectorAll('.selected-tag').forEach(tag => {
            tag.addEventListener('click', (e) => {
                const tagId = parseInt(e.target.dataset.id);
                const tag = this.selectedTags.find(t => t.id === tagId);
                if (tag) this.removeTag(tag);
            });
        });

        // Attach dropdown event listeners
        const countrySelect = this.querySelector('.country');
        const stateSelect = this.querySelector('.state');
        const citySelect = this.querySelector('.city');

        if (countrySelect) {
            countrySelect.addEventListener('change', () => this.loadStates());
        }
        if (stateSelect) {
            stateSelect.addEventListener('change', () => this.loadCities());
        }

        // Attach image preview functionality
        const imageInput = this.querySelector('#profile');
        const imagePreview = this.querySelector('#imagePreview');

        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                        imageInput.style.opacity = "10%";
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.style.display = 'none';
                }
            });
        }
    }

    loadCountries() {
        const config = {
            cUrl: 'https://api.countrystatecity.in/v1/countries',
            ckey: 'NHhvOEcyWk50N2Vna3VFTE00bFp3MjFKR0ZEOUhkZlg4RTk1MlJlaA=='
        };

        const countrySelect = this.querySelector('.country');
        const stateSelect = this.querySelector('.state');
        const citySelect = this.querySelector('.city');

        if (!countrySelect || !stateSelect || !citySelect) return;

        fetch(config.cUrl, { headers: { "X-CSCAPI-KEY": config.ckey } })
            .then(response => response.json())
            .then(data => {
                
                data.forEach(country => {
                    const option = document.createElement('option');
                    option.value = country.name;
                    this.querySelector('.country').setAttribute("data-state-name", country.name);
                    countrySelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading countries:', error));


        stateSelect.disabled = true;
        citySelect.disabled = true;
        stateSelect.style.pointerEvents = 'none';
        citySelect.style.pointerEvents = 'none';
    }

    loadStates() {
        const config = {
            cUrl: 'https://api.countrystatecity.in/v1/countries',
            ckey: 'NHhvOEcyWk50N2Vna3VFTE00bFp3MjFKR0ZEOUhkZlg4RTk1MlJlaA=='
        };
    
        const countrySelect = this.querySelector('.country');
        const stateSelect = this.querySelector('.state');
        const citySelect = this.querySelector('.city');
    
        if (!countrySelect || !stateSelect || !citySelect) return;
    
        const selectedCountryCode = countrySelect.value;
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';
    
        fetch(`${config.cUrl}/${selectedCountryCode}/states`, {
            headers: {
                "X-CSCAPI-KEY": config.ckey
            }
        })
        .then(response => response.json())
        .then(data => {
            data.forEach(state => {
                const option = document.createElement('option');
                option.value = state.iso2; // Use state code (e.g., "NL" for Nagaland)
                option.textContent = state.name; // Display state name (e.g., "Nagaland")
                this.querySelector('.state').setAttribute("data-state-name", state.name);
                stateSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error loading states:', error));
    
        stateSelect.disabled = false;
        citySelect.disabled = true;
        stateSelect.style.pointerEvents = 'auto';
        citySelect.style.pointerEvents = 'none';
    }

    loadCities() {
        const config = {
            cUrl: 'https://api.countrystatecity.in/v1/countries',
            ckey: 'NHhvOEcyWk50N2Vna3VFTE00bFp3MjFKR0ZEOUhkZlg4RTk1MlJlaA=='
        };
    
        const countrySelect = this.querySelector('.country');
        const stateSelect = this.querySelector('.state');
        const citySelect = this.querySelector('.city');
    
        if (!countrySelect || !stateSelect || !citySelect) return;
    
        const selectedCountryCode = countrySelect.value; // Country code (e.g., "IN")
        const selectedStateCode = stateSelect.value; // State code (e.g., "NL" for Nagaland)
    
        // Log the selected country and state
        console.log("Selected Country Code:", selectedCountryCode);
        console.log("Selected State Code:", selectedStateCode);
    
        // Clear existing city options
        citySelect.innerHTML = '<option value="">Select City</option>';
    
        // Fetch cities
        fetch(`${config.cUrl}/${selectedCountryCode}/states/${selectedStateCode}/cities`, {
            headers: {
                "X-CSCAPI-KEY": config.ckey
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Cities Data:", data);
    
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.name;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });
    
            citySelect.disabled = false;
            citySelect.style.pointerEvents = 'auto';
        })
        .catch(error => {
            console.error('Error loading cities:', error);
            const notyf = new Notyf({
                duration: 5000,
                position: { x: 'right', y: 'top' },
                types: [
                    { type: 'error', background: '#FF5252', icon: false }
                ]
            });
            notyf.error("Failed to load cities. Please try again.");
        });
    }
    render() {
        const userData = JSON.parse(localStorage.getItem("authUserData")) || {};
        let username,password,teleUsername,phone,biography = "";

        
        if(userData) {
            username = userData.username
            biography = userData.bio
            password = userData.password
            teleUsername = userData.telegram_username
            phone = userData.telegram_phone
           
            
          

            

        };

      

        const usernameInput = this.querySelector('#username');
        const passwordInput = this.querySelector('#password');
        const biographyInput = this.querySelector('#description');
        const telegramInput = this.querySelector('#telegram');
        const phoneInput = this.querySelector('#phone');
        
        // Assign values only after ensuring elements exist
        const usernameValue = usernameInput?.value.trim() || username || '';
        const biographyValue = biographyInput?.value.trim() || biography || "";
        const phoneValue = phoneInput?.value.trim() || phone || "";
        const passwordValue = passwordInput?.value.trim() || password || "";
        const telegramValue = telegramInput?.value.trim() || teleUsername || "";
        
        // Ensure placeholder shows when input is empty
        if (usernameInput && !username) {
            usernameInput.value = ''; // Clear any existing value
            usernameInput.placeholder = "Enter username";
        }
        
        if (passwordInput && !password) {
            passwordInput.value = '';
            passwordInput.placeholder = "Enter description";
        }

        if (phoneInput && !phone) {
            phoneValue.value = '';
            phoneValue.placeholder = "Enter description";
        }

        if (biographyInput && !biography) {
            biographyInput.value = '';
            biographyInput.placeholder = "Enter description";
        }

        if (telegramInput && !teleUsername) {
            telegramInput.value = '';
            telegramInput.placeholder = "Enter description";
        }

        
        this.innerHTML = `
            <div class="max-w-2xl mx-auto p-8">
                <h1 class="font-normal text-8xl leading-[112px] text-[#2F2F2F] pt-8 mb-16">Your Profile</h1>
                <form class="space-y-6">
                    <!-- Profile Image Input and Preview -->
                    <div class="relative">
                        <label for="profile" class="block text-sm font-medium text-gray-700 mb-2">Profile Image</label>
                        <div class="relative">
                            <input type="file" 
                                   id="profile" 
                                   accept=".jpg,.jpeg,.png,.jfif,image/jpeg,image/png,image/jfif"
                                   class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                          shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                          focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                          text-gray-700 file:mr-4 file:py-2 file:px-4
                                          file:rounded-full file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-[#91C4F2] file:text-white
                                          hover:file:bg-[#7ab0e2]
                                          relative z-10">
                            <div class="absolute inset-0 flex items-center justify-center z-0">
                                <img id="imagePreview"
                                     src="#"
                                     alt="Profile preview"
                                     class="w-32 h-32 rounded-full object-cover hidden"
                                     style="display: none;">
                            </div>
                        </div>
                    </div>

                     <div class="relative">
                        <label for="telegram" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" 
                               id="username" 
                               placeholder="username"
                               value="${username}"
                               class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                      shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                      text-gray-700 placeholder-gray-400">
                    </div>

                     

                    <!-- Telegram Input -->
                    <div class="relative">
                        <label for="telegram" class="block text-sm font-medium text-gray-700 mb-2">Telegram Username</label>
                        <input type="text" 
                               id="telegram" 
                               value="${teleUsername}"
                               placeholder="@username"
                               class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                      shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                      text-gray-700 placeholder-gray-400">
                    </div>

                    <!-- Phone Number Input -->
                    <div class="relative">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" 
                               id="phone" 
                               value="${phone}"
                               pattern="[0-9]{3}-[0-9]{3}-[0-9]{5}"
                               placeholder="123-456-7890"
                               class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                      shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                      text-gray-700 placeholder-gray-400">
                    </div>

                 

                    <!-- Country, State, and City Dropdowns -->
                  
                    
                    <div class="input-group relative">
                    <label for="telegram" class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                        <select class="form-select country w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                      shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                      text-gray-700 placeholder-gray-400" aria-label="Default select example">
                            <option selected>Country</option>
                        </select>
                    </div>
                    <div class="input-group relative">
                    <label for="telegram" class="block text-sm font-medium text-gray-700 mb-2">Region</label>
                        <select class="form-select state w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                      shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                      text-gray-700 placeholder-gray-400" aria-label="Default select example">
                            <option selected>Region</option>
                        </select>
                    </div>
                    <div class="input-group relative">
                    <label for="telegram" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                        <select class="form-select city w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                      shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                      text-gray-700 placeholder-gray-400" aria-label="Default select example">
                            <option selected>City</option>
                        </select>
                    </div>

                    <!-- Description Textarea -->
                    <div class="relative">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Biography</label>
                        <textarea id="description" 
                                  required
                                  rows="4" 
                                  placeholder="Enter bio"
                                  class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                         shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                         focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                         text-gray-700 placeholder-gray-400">
                                         ${biography}
                                         </textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4 justify-evenly">
                        <but-ton class="p-4 rounded-full bg-[#91C4F2]" text="Submit" color="#91C4F2"></but-ton>
                       
                    </div>
                </form>
            </div>
        `;
    }
}

customElements.define('profile-form', ProfileForm);