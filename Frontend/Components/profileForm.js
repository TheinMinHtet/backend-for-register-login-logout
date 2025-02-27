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
        const formData = {
            title: this.querySelector('#title').value,
            description: this.querySelector('#description').value,
            tags: this.selectedTags
        };
        console.log('Form submitted:', formData);
        // Reset form
        this.querySelector('form').reset();
       
        this.render();
        this.attachEventListeners();
    }

    attachEventListeners() {
        const imageInput = this.querySelector('#profile');
        imageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            const imagePreview = this.querySelector('#imagePreview');
            
            if (file) {
                // Check file type
                const validTypes = ['image/jpeg', 'image/png', 'image/jfif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, or JFIF)');
                    e.target.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }

                // Create preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    imageInput.classList.add('opacity-20'); // Add opacity when file is selected
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
                imageInput.classList.remove('opacity-20'); // Remove opacity when no file
            }
        });

        const form = this.querySelector('form');
        form.addEventListener('submit', (e) => this.handleSubmit(e));

       

        this.querySelectorAll('.selected-tag').forEach(tag => {
            tag.addEventListener('click', (e) => {
                const tagId = parseInt(e.target.dataset.id);
                const tag = this.selectedTags.find(t => t.id === tagId);
                if (tag) this.removeTag(tag);
            });
        });
    }

    render() {
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

                    <!-- Telegram Input -->
                    <div class="relative">
                        <label for="telegram" class="block text-sm font-medium text-gray-700 mb-2">Telegram Username</label>
                        <input type="text" 
                               id="telegram" 
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
                               pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}"
                               placeholder="123-456-7890"
                               class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                      shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                      text-gray-700 placeholder-gray-400">
                    </div>

                   
                    <!-- Title Input -->
                    <div class="relative">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input type="text" 
                               id="title" 
                               required
                               placeholder="Enter title"
                               class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                      shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                      text-gray-700 placeholder-gray-400">
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
                                         text-gray-700 placeholder-gray-400"></textarea>
                    </div>

                   

                    <!-- Action Buttons -->
                    <div class="flex gap-4 justify-evenly">
                        <but-ton class="p-4 rounded-full bg-[#91C4F2]" text="Submit" color="#91C4F2"></but-ton>
                        <but-ton class="p-4 rounded-full bg-[#FFA9AA]" text="Delete" color="#FFA9AA" border="8px 8px 16px #FF8687, -8px -8px 16px #FEC3C3"></but-ton>
                    </div>
                </form>
            </div>
        `;
    }
}

customElements.define('profile-form', ProfileForm);