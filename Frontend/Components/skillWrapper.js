class SkillWrapper extends HTMLElement {
    constructor() {
        super();
        this.originalSkills = JSON.parse(localStorage.getItem("skillsData"));
        this.skills = [...this.originalSkills];
        
        // Bind methods
        this.handleSort = this.handleSort.bind(this);
    }

    connectedCallback() {
        this.render();
        this.addEventListener('sort', this.handleSort);
    }

    disconnectedCallback() {
        this.removeEventListener('sort', this.handleSort);
    }

    handleSort(event) {
        const sortValue = event.detail.value;
        
        switch(sortValue) {
            case 'title-asc':
                this.skills.sort((a, b) => a.name.localeCompare(b.title));
                break;
            case 'title-desc':
                this.skills.sort((a, b) => b.name.localeCompare(a.title));
                break;
            case 'tags-asc':
                this.skills.sort((a, b) => a.tags.length - b.tags.length);
                break;
            case 'tags-desc':
                this.skills.sort((a, b) => b.tags.length - a.tags.length);
                break;
            default:
                this.skills = [...this.originalSkills];
        }
        
        this.render();
    }

    render() {
        this.innerHTML = `
            <div class="container mx-auto">
                <results-header class="box-border flex justify-around items-center px-16 py-8 gap-[173px] w-full h-[96px] border-b border-[#2f2f2f1c]">
                    <results-count count="${this.skills.length}"></results-count>
                    <sort-button></sort-button>
                </results-header>
                
                <div class="w-full flex flex-row items-center mt-8">
                    <div class="flex gap-[150px] flex-wrap justify-center px-14">
                        ${this.skills.map(skill => `
                            <sk-ill 
                                title="${skill.name}" 
                                description="${skill.description}" 
                                tags='${JSON.stringify(skill.tags)}'
                                days="${skill.days}"
                                taught_count=${skill.taught_count}>
                            </sk-ill>
                        `).join("")}
                    </div>
                </div>
            </div>
        `;
    }
}

customElements.define("skill-wrapper", SkillWrapper);