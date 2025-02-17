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
        this.selectedTags = [];
        this.availableTags = [
            { id: 1, text: "music", color: "#F2FCE2" },
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
                                  class="w-full px-6 py-4 bg-[#F1F0FB] rounded-xl border-none 
                                         shadow-[inset_4px_4px_8px_rgba(0,0,0,0.1),inset_-4px_-4px_8px_rgba(255,255,255,0.9)]
                                         focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                         text-gray-700 placeholder-gray-400"></textarea>
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
