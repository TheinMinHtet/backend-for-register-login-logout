class MemoryForm extends HTMLElement {
    constructor() {
        super();
        this.getRandomColor = () => {
            const colors = ['#BEFBFF', '#C8FFBE', '#FFF7BE', '#FFBEC7', '#BEC7FF'];
            return colors[Math.floor(Math.random() * colors.length)];
        };
        this.owned = false;
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
    }

    connectedCallback() {
        this.render();
        this.attachEventListeners();
        this.fetchSkills();
    }

    async fetchSkills() {
        const token = localStorage.getItem('JWT');
        if (!token) {
            console.error('No JWT token found in localStorage.');
            return;
        }

        try {
            const response = await fetch('http://localhost/skillSwap/skill-swap/skill_crud.php', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to fetch skills');
            }

            const data = await response.json();

            // Extract the skills array from the response
            const skills = data.skill;

            // Map skills to availableTags
            this.availableTags = skills.map(skill => ({
                id: skill.skill_id, // Use skill_id as the id
                text: skill.name,   // Use skill name as the text
                color: this.getRandomColor(), // Assign a random color
            }));

            // Re-render the component to reflect the updated tags
            this.render();
            this.attachEventListeners();
        } catch (error) {
            console.error('Error fetching skills:', error);
        }
    }

    addTag(tag) {
        if (!this.selectedTags.find(t => t.id === tag.id)) {
            this.selectedTags.push(tag);
            this.availableTags = this.availableTags.filter(t => t.id !== tag.id);
            this.render();
            this.attachEventListeners(); // Reattach event listeners after render
        }
    }

    previewImage(event) {
        const fileInput = event.target;
        const previewImage = document.getElementById('previewImage');
        const plusIcon = document.getElementById('plusIcon');
    
        if (fileInput.files && fileInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
                plusIcon.style.display = 'none'; // Hide the plus icon when an image is selected
            };
            reader.readAsDataURL(fileInput.files[0]);
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

    async handleSubmit(e) {
        e.preventDefault();
    
        // Get form data
        const description = this.querySelector('#description').value.trim();
        const imageInput = this.querySelector('#fileInput');
        const imageFile = imageInput.files[0];
        const skill_id = null;
    
        if (!description || !imageFile) {
            alert('Description and image are required.');
            return;
        }
    
        // Get the selected skill_id from tags
    
        // Prepare form data
        const formData = new FormData();
        formData.append('description', description);
        
       
        if (skill_id !== null) {
            formData.append('skill_id', 45);
        }

      
        
    
        // Append the image file correctly
        formData.append('image', imageFile, imageFile.name); // Add the file name as the third argument
    
        // Debugging: Log FormData values
        for (let [key, value] of formData.entries()) {
            console.log(`${key}:`, value);
        }
    
        // Store in localStorage for testing
        const formObject = {};
        formData.forEach((value, key) => {
            formObject[key] = value instanceof File ? value.name : value; // Store file name instead of File object
        });
        localStorage.setItem("test", JSON.stringify(formObject));
    
        // Get JWT token from localStorage
        const token = localStorage.getItem('JWT');
        if (!token) {
            alert('You are not authenticated. Please log in.');
            return;
        }

        const authUser = localStorage.getItem("authUser");
        const memoryId = localStorage.getItem("memoryIdfg")
        const memoryData = JSON.parse(localStorage.getItem("memoryDatafg")) || {};
        let method = 'POST';
        let url = 'http://localhost/skillSwap/skill-swap/memory_crud.php/upload';

        if(memoryData && authUser == memoryData.user_id) {
            url = url = 'http://localhost/skillSwap/skill-swap/memory_crud.php/edit';
        
            formData.append('memory_id',memoryId)
            alert('hi')

        }
    
    try {
            // Send POST request to upload memory
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                },
                body: formData, // Do NOT set 'Content-Type', browser handles it automatically
            });
            
            const result = await response.json();
            if (result.status === 'success') {
                alert('Memory uploaded successfully!');
                this.resetForm();
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error uploading memory:', error);
            alert('Failed to upload memory. Please try again.');
        }
    }
    

    async handleDelete() {
        // Get memory_id from localStorage (or another source)
        const memory_id = localStorage.getItem('memory_id');
        if (!memory_id) {
            alert('Memory ID is required for deletion.');
            return;
        }

        // Get JWT token from localStorage
        const token = localStorage.getItem('JWT');
        if (!token) {
            alert('You are not authenticated. Please log in.');
            return;
        }

        try {
            // Send DELETE request to delete memory
            const response = await fetch('http://localhost/skillSwap/skill-swap/memory_crud.php', {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `memory_id=${memory_id}`,
            });

            const result = await response.json();
            if (result.status === 'success') {
                alert('Memory deleted successfully!');
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error deleting memory:', error);
            alert('Failed to delete memory. Please try again.');
        }
    }

    resetForm() {
        this.querySelector('form').reset();
        this.selectedTags = [];
        this.availableTags = [
            { id: 45, text: "music", color: "#F2FCE2" },
            { id: 2, text: "law", color: "#FFDEE2" },
            { id: 3, text: "tech", color: "#E5DEFF" },
            { id: 4, text: "sports", color: "#D3E4FD" },
            { id: 5, text: "cooking", color: "#FEC6A1" },
            { id: 6, text: "writing", color: "#FEF7CD" },
            { id: 7, text: "photography", color: "#FDE1D3" },
            { id: 8, text: "design", color: "#E5DEFF" }
        ];
        this.render();
        this.attachEventListeners();
    }

    attachEventListeners() {
        const form = this.querySelector('form');
        form.addEventListener('submit', (e) => this.handleSubmit(e));

        const fileInput = this.querySelector('#fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', (e) => this.previewImage(e));
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

        // Attach delete button click handler
        const deleteButton = this.querySelector('.delete-button');
        if (deleteButton) {
            deleteButton.addEventListener('click', () => this.handleDelete());
        }
    }

    render() {
        const memoryId = localStorage.getItem("memoryIdfg");
        const authUser = localStorage.getItem("authUser");
        const memoryData = JSON.parse(localStorage.getItem("memoryDatafg")) || {};
        let img_name,description = "";

        
        if(memoryData) {
            img_name = memoryData.img_name;
            description = memoryData.description;

            

        };

        if(authUser == memoryData.user_id) {
            this.owned = true;

        };

        this.innerHTML = `
            <div class="max-w-2xl mx-auto p-8">
                <h1 class="font-normal text-8xl leading-[112px] text-[#2F2F2F] pt-8 mb-16">Your Memory</h1>
                <form class="space-y-6">
                    <!-- Image Upload -->
                    <div class="frame">
                        <div class="upload-box" onclick="document.getElementById('fileInput').click()">
    <input type="file" id="fileInput" accept="image/*" hidden ${!this.owned ? 'disabled' : '  '}>
    <span id="plusIcon" style="display: ${img_name ? 'none': 'block'}">+</span>
    <img id="previewImage" src="../../${img_name}" alt="Preview" style="display: ${img_name ? 'block' : 'none'}; max-width: 100%; max-height: 411px;">
</div>
                    </div>
    
                    <!-- Description Textarea -->
                    <div class="relative">
                        <textarea id="description" 
                                  required
                                  rows="4" 
                                  placeholder="Enter description"
                                  class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                         shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                         focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                         text-gray-700 placeholder-gray-400" ${!this.owned ? 'disabled' : ''}>
                                         ${description}</textarea>
                    </div>
    
                    <!-- Tags Section -->
                    <div class="space-y-4">
                    ${this.owned ? `
                        <h3 class="text-lg font-medium text-gray-700">Available Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            ${this.availableTags.map(tag => `
                                <button type="button"
                                        data-id="${tag.id}"
                                        class="available-tag px-4 py-1.5 rounded-full text-sm
                                               transition-all duration-200 ease-in-out hover:scale-105"
                                        style="background-color: ${tag.color}; color: rgba(0,0,0,0.7)">
                                    ${tag.text}
                                </button>
                            `).join('')}
                        </div>
                        `: ``}
                        
    
                        <h3 class="text-lg font-medium text-gray-700">Selected Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            ${this.selectedTags.map(tag => `
                                <button type="button"
                                        data-id="${tag.id}"
                                        class="selected-tag px-4 py-1.5 rounded-full text-sm
                                               transition-all duration-200 ease-in-out hover:scale-105"
                                        style="background-color: ${tag.color}; color: rgba(0,0,0,0.7)">
                                    ${tag.text} ×
                                </button>
                            `).join('')}
                        </div>
                    </div>
    
                    <!-- Action Buttons -->
                    <div class="flex gap-4 justify-evenly">
                        <button type="submit" class="p-4 rounded-full bg-[#91C4F2]">Submit</button>
                        ${this.owned ? '<button type="button" class="delete-button p-4 rounded-full bg-[#FFA9AA]">Delete</button>' : ''}
                    </div>
                </form>
            </div>
        `;
    }
}

customElements.define('memory-form', MemoryForm);