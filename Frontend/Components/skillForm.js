class SkillForm extends HTMLElement {
    constructor() {
        super();
        this.getRandomColor = () => {
            const colors = ['#BEFBFF', '#C8FFBE', '#FFF7BE', '#FFBEC7', '#BEC7FF'];
          return colors[Math.floor(Math.random() * colors.length)];
    
          };
          this.availableTags = [
            { id: 1, tag: "music", color: this.getRandomColor() },
            { id: 2, tag: "law", color: this.getRandomColor() },
            { id: 3, tag: "tech", color: this.getRandomColor() },
            { id: 4, tag: "sports", color: this.getRandomColor() },
            { id: 5, tag: "cooking", color: this.getRandomColor() },
            { id: 6, tag: "writing", color: this.getRandomColor() },
            { id: 7, tag: "photography", color: this.getRandomColor() },
            { id: 8, tag: "design", color: this.getRandomColor() },
            { id: 9, tag: "programming", color: this.getRandomColor() },
            { id: 10, tag: "web development", color: this.getRandomColor() },
            { id: 11, tag: "mobile development", color: this.getRandomColor() },
            { id: 12, tag: "AI & machine learning", color: this.getRandomColor() },
            { id: 13, tag: "cybersecurity", color: this.getRandomColor() },
            { id: 14, tag: "data science", color: this.getRandomColor() },
            { id: 15, tag: "game development", color: this.getRandomColor() },
            { id: 16, tag: "blockchain", color: this.getRandomColor() },
            { id: 17, tag: "graphic design", color: this.getRandomColor() },
            { id: 18, tag: "UI/UX design", color: this.getRandomColor() },
            { id: 19, tag: "video editing", color: this.getRandomColor() },
            { id: 20, tag: "animation", color: this.getRandomColor() },
            { id: 21, tag: "illustration", color: this.getRandomColor() },
            { id: 22, tag: "interior design", color: this.getRandomColor() },
            { id: 23, tag: "fashion design", color: this.getRandomColor() },
            { id: 24, tag: "digital marketing", color: this.getRandomColor() },
            { id: 25, tag: "SEO", color: this.getRandomColor() },
            { id: 26, tag: "copywriting", color: this.getRandomColor() },
            { id: 27, tag: "e-commerce", color: this.getRandomColor() },
            { id: 28, tag: "finance", color: this.getRandomColor() },
            { id: 29, tag: "entrepreneurship", color: this.getRandomColor() },
            { id: 30, tag: "yoga", color: this.getRandomColor() },
            { id: 31, tag: "meditation", color: this.getRandomColor() },
            { id: 32, tag: "nutrition", color: this.getRandomColor() },
            { id: 33, tag: "personal training", color: this.getRandomColor() },
            { id: 34, tag: "mental health coaching", color: this.getRandomColor() },
            { id: 35, tag: "teaching", color: this.getRandomColor() },
            { id: 36, tag: "language learning", color: this.getRandomColor() },
            { id: 37, tag: "public speaking", color: this.getRandomColor() },
            { id: 38, tag: "sign language", color: this.getRandomColor() },
            { id: 39, tag: "football", color: this.getRandomColor() },
            { id: 40, tag: "basketball", color: this.getRandomColor() },
            { id: 41, tag: "swimming", color: this.getRandomColor() },
            { id: 42, tag: "hiking", color: this.getRandomColor() },
            { id: 43, tag: "martial arts", color: this.getRandomColor() }
        ];
        
        this.selectedTags = JSON.parse(localStorage.getItem("skillTagfg")) || [];
        this.owned = false;

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

   
  

    async handleDelete(e) {
        e.preventDefault();
        const notyf = new Notyf();

        const token = localStorage.getItem('JWT');
        if (!token) {
            notyf.error('Unauthorized! Please log in.');
            return;
        }

        let formData = {
            skill_id:  localStorage.getItem("skillIdfg"),
        };

        try {
            const response = await fetch('http://localhost/skillSwap/skill-swap/skill_crud.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.status === "success") {
                notyf.success(result.message || 'Skill added successfully!');
                window.location.href = '../Home/index.html';
                
            } else {
                notyf.success(result.message || 'An error occurred.');
                
            }
        } catch (error) {
            notyf.error('Network error. Please try again later.');
        }

        setTimeout(() => {
            window.location.href = "../Home/index.html";
        }, 300);
        this.render();
        this.attachEventListeners();

    }


    async handleSubmit(e) {
    e.preventDefault();
    const notyf = new Notyf();

    const title = this.querySelector('#title').value.trim();
    const description = this.querySelector('#description').value.trim();
    const days = this.querySelector('#days').value.trim();

    // Validate title length (less than 10 words)
    const titleWordCount = title.split(/\s+/).filter(word => word.length > 0).length;
    if (titleWordCount > 10) {
        notyf.error('Title should be less than 10 words!');
        return;
    }

    // Validate required fields
    if (!title || !description || !days || this.selectedTags.length === 0) {
        notyf.error('All fields are required!');
        return;
    }

    if (title.length > 10) {
        notyf.error('title should be less than 10 characters');
        return;
    }

    const token = localStorage.getItem('JWT');
    if (!token) {
        notyf.error('Unauthorized! Please log in.');
        return;
    }

    // Get existing skill data from localStorage
    const skillData = JSON.parse(localStorage.getItem("skillDatafg")) || {};
    const authUser = localStorage.getItem("authUser");

    let formData = {
        title: skillData.title || title, // Use existing title if available, otherwise use the new input
        description: skillData.description || description, // Use existing description if available
        tags: this.selectedTags.map(tag => tag.tag),
        hours: skillData.hours || days // Use existing hours if available
    };

    let method = 'POST';
    let url = 'http://localhost/skillSwap/skill-swap/skill_crud.php';

    // If editing (skillData exists and authUser matches skill owner)
    if (skillData && authUser == skillData.user_id) {
        method = 'PUT';
        formData = {
            skill_id: localStorage.getItem("skillIdfg"), // Include skill_id for editing
            title: title, // Use the new title from the input
            description: description, // Use the new description from the input
            tags: this.selectedTags.map(tag => tag.tag),
            hours: days // Use the new hours from the input
        };
    }

    // If requesting (authUser is not the skill owner)
    if (skillData && skillData.user_id && authUser != skillData.user_id) {
        console.log("Notification case: authUser != skillData.user_id");
        url = 'http://localhost/skillSwap/skill-swap/notification_page.php';
        formData = {
            teacher_id: skillData.user_id,
            skill_id: localStorage.getItem("skillIdfg")
        };
    }

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(formData)
        });

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const result = await response.json();
            if (response.ok) {
                notyf.success(result.message || 'Skill added successfully!');
              
            } else {
                notyf.success('Cannot be requested');
           
            }
        } else {
            const text = await response.text();
            throw new Error(`Expected JSON, got: ${text}`);
        }
    } catch (error) {
        notyf.error(error.message);
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

     
        

        this.querySelector('#delete').addEventListener('click', (e) => this.handleDelete(e));
    }

    render() {
        const skillId = localStorage.getItem("skillIdfg");
        const authUser = localStorage.getItem("authUser");
      
        const skillData = JSON.parse(localStorage.getItem("skillDatafg")) || {};
        let name,description,hours = "";

        
        if(skillData) {
            name = skillData.name;
            description = skillData.description;
            hours = skillData.hours;
           
            
          

            

        };

        if (authUser == skillData.user_id || (Object.keys(skillData).length === 0 && !skillId)) {
            this.owned = true;
        }

        const titleInput = this.querySelector('#title');
        const descriptionInput = this.querySelector('#description');
        
        // Assign values only after ensuring elements exist
        const titleValue = titleInput?.value.trim() || name || '';
        const descriptionValue = descriptionInput?.value.trim() || description || "";
        
        // Ensure placeholder shows when input is empty
        if (titleInput && !name) {
            titleInput.value = ''; // Clear any existing value
            titleInput.placeholder = "Enter title";
        }
        
        if (descriptionInput && !description) {
            descriptionInput.value = '';
            descriptionInput.placeholder = "Enter description";
        }
        

        const numValue = 5;
        this.innerHTML = `
            <div class="max-w-2xl mx-auto p-8">
                <h1 class="font-semibold text-8xl leading-[112px] text-[#2F2F2F] pt-8 mb-16 
           bg-[#f1f5f9] rounded-[20px] px-12 py-6 
           shadow-[8px_8px_16px_#bebebe,-8px_-8px_16px_#ffffff] 
           transition-all duration-300 hover:shadow-[4px_4px_8px_#bebebe,-4px_-4px_8px_#ffffff]">
  ${this.owned ? 'Your Skill' : 'Skill'}
</h1>

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
                                      text-gray-700 placeholder-gray-400" ${this.owned ? '' : 'disabled'}>
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
                                         text-gray-700 placeholder-gray-400" ${this.owned ? '' : 'disabled'}>${descriptionValue}</textarea>
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
                                      text-gray-700 placeholder-gray-400" ${this.owned ? '' : 'disabled'} hidden>
                    </div>

                    <!-- Tags Section -->
                    <div class="space-y-4">
                        ${this.owned ? '<h3 class="text-lg font-medium text-gray-700">Available Tags</h3>' : ''}
                        ${this.owned ? `<div class="flex flex-wrap gap-2">
                            ${this.availableTags.map(tag => `
                                <button type="button"
                                        data-id="${tag.id}"
                                        class="available-tag px-4 py-1.5 rounded-full text-sm
                                               transition-all duration-200 ease-in-out hover:scale-105"
                                        style="background-color: ${this.getRandomColor()}; color: rgba(0,0,0,0.7)">
                                    ${tag.tag}
                                </button>
                            `).join('')}
                        </div>` : ''}

                        <h3 class="text-lg font-medium text-gray-700">Selected Tags</h3>
                
                            <div class="flex flex-wrap gap-2">
                              ${this.selectedTags.map(tag => `
                                <button type="button"
                                        data-id="${tag.id}"
                                        class="selected-tag px-4 py-1.5 rounded-full text-sm
                                               transition-all duration-200 ease-in-out hover:scale-105"
                                        style="background-color: ${this.getRandomColor()}; color: rgba(0,0,0,0.7)">
                                  ${tag.tag} ${(this.owned && (Object.keys(skillData).length === 0)) ? `×` : ``}
                                </button>
                              `).join('')}
                            </div> 
                    
                            
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4 justify-evenly">
                    ${this.owned ? '<but-ton class="p-4 rounded-full bg-[#91C4F2]" text="Submit" color="#91C4F2"></but-ton>': '<but-ton class="p-4 rounded-full bg-[#91C4F2]" text="Request" color="#91C4F2"></but-ton>'}
                        

                        ${(this.owned && (Object.keys(skillData).length !== 0))  ? '<but-ton id="delete" class="p-4 rounded-full bg-[#FFA9AA]" text="Delete" color="#FFA9AA" border="8px 8px 16px #FF8687, -8px -8px 16px #FEC3C3"></but-ton>': ""}
                        
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
