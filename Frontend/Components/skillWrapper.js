class SkillWrapper extends HTMLElement {
    constructor() {
        super();
        this.originalSkills = [
            { title: "Guitar", description: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut eu mollis tortor, sed posuere elit. Pellentesque sed imperdiet arcu, non interdum libero. Mauris non mi id enim volutpat efficitur. Donec quis eros at nunc maximus tristique. Nunc pretium risus magna, et vehicula leo tincidunt nec. Mauris mollis vehicula ante ac sollicitudin. Nullam non justo at purus accumsan aliquam.", tags: [{ text: "boy" }, { text: "girl" }] },
            { title: "Piano", description: "Mastering the piano notes.", tags: [{ text: "music" }, { text: "classic" }] },
            { title: "Coding", description: "Building amazing projects.", tags: [{ text: "developer" }, { text: "tech" }] },
            { title: "Photography", description: "Capturing the beauty of life.", tags: [{ text: "lens" }, { text: "nature" }] },
            { title: "Dancing", description: "Expressing with movement.", tags: [{ text: "hiphop" }, { text: "freestyle" }] }
        ];
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
                this.skills.sort((a, b) => a.title.localeCompare(b.title));
                break;
            case 'title-desc':
                this.skills.sort((a, b) => b.title.localeCompare(a.title));
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
                                title="${skill.title}" 
                                description="${skill.description}" 
                                tags='${JSON.stringify(skill.tags)}'>
                            </sk-ill>
                        `).join("")}
                    </div>
                </div>
            </div>
        `;
    }
}

customElements.define("skill-wrapper", SkillWrapper);