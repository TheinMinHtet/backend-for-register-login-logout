class SkillForm extends HTMLElement {
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


    async handleSubmit(e) {
        e.preventDefault();
        const notyf = new Notyf();
        
        const title = this.querySelector('#title').value.trim();
        const description = this.querySelector('#description').value.trim();
        const days = this.querySelector('#days').value.trim();


        if (!title || !description || !days || this.selectedTags.length === 0) {
            notyf.error('All fields are required!');
            return;
        }

        const token = localStorage.getItem('JWT');
        if (!token) {
            notyf.error('Unauthorized! Please log in.');
            return;
        }

        const formData = {
            title,
            description,
            tags: this.selectedTags.map(tag => tag.text),
            hours: days // Sending tags as an array of strings
        };

        console.log(formData)

        try {
            const response = await fetch('http://localhost/skillSwap/skill-swap/skill_crud.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.status === "success") {
                notyf.success(result.message || 'Skill added successfully!');
                this.resetForm();
            } else {
                notyf.error(result.message || 'An error occurred.');
            }
        } catch (error) {
            notyf.error('Network error. Please try again later.');
        }
        this.render();
        this.attachEventListeners();
    }

    attachEventListeners() {
        const form = this.querySelector('form');
        form.addEventListener('submit', (e) => this.handleSubmit(e));

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
    }

    render() {
        const titleValue = this.querySelector('#title')?.value || '';
        const descriptionValue = this.querySelector('#description')?.value || '';
        const numValue = this.querySelector("#days")?.value || '';
        this.innerHTML = `
            <div class="max-w-2xl mx-auto p-8">
                <h1 class="font-normal text-8xl leading-[112px] text-[#2F2F2F] pt-8 mb-16">Your Skill</h1>
                <form class="space-y-6">
                    <!-- Title Input -->
                    <div class="relative">
                        <input type="text" 
                               id="title" 
                               required
                               placeholder="Enter title"
                               value="${titleValue}"
                               class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                      shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                      text-gray-700 placeholder-gray-400">
                    </div>

                    <!-- Description Textarea -->
                    <div class="relative">
                        <textarea id="description" 
                                  required
                                  rows="4" 
                                  placeholder="Enter description"
                                  value="${descriptionValue}"
                                  class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                         shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                         focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                         text-gray-700 placeholder-gray-400"></textarea>
                    </div>

                    <!-- Number -->
                    <div class="relative">
                        <input type="number" 
                               id="days" 
                               required
                               placeholder="Enter days"
                               value="${numValue}"
                               class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                      shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                      text-gray-700 placeholder-gray-400">
                    </div>

                    <!-- Tags Section -->
                    <div class="space-y-4">
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
                        <but-ton class="p-4 rounded-full bg-[#91C4F2]" text="Submit" color="#91C4F2"></but-ton>
                        <but-ton class="p-4 rounded-full bg-[#FFA9AA]" text="Delete" color="#FFA9AA" border="8px 8px 16px #FF8687, -8px -8px 16px #FEC3C3"></but-ton>
                        
                    </div>
                </form>
            </div>
        `;
    }
}

customElements.define('skill-form', SkillForm);

/* knob-elevation */

/* Auto layout */
// display: flex;
// flex-direction: row;
// justify-content: center;
// align-items: center;
// padding: 24px;
// gap: 8px;

// width: 153px;
// height: 48px;

// background: #FFA9AA;
// box-shadow: 8px 8px 16px #FF8687, -8px -8px 16px #FEC3C3;
// border-radius: 1e+11px;

// /* Inside auto layout */
// flex: none;
// order: 1;
// align-self: stretch;
// flex-grow: 1;
// z-index: 1;
