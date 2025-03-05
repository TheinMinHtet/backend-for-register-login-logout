class SkillWrapper extends HTMLElement {
    constructor() {
        super();
        const storedData = localStorage.getItem("skillsData");
        this.originalSkills = storedData ? JSON.parse(storedData) : [];
        this.skills = [...this.originalSkills];

        this.handleSort = this.handleSort.bind(this);

        this.limitWords = (text, limit) => {
            if (!text) return "";
            const words = text.split(/\s+/); // Ensure words are split properly
            return words.length > limit ? words.slice(0, limit).join(" ") + " ..." : text;
        };
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
                this.skills.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'title-desc':
                this.skills.sort((a, b) => b.name.localeCompare(a.name));
                break;
            case 'tags-asc':
                this.skills.sort((a, b) => a.tags.length - b.tags.length);
                break;
            case 'tags-desc':
                this.skills.sort((a, b) => b.tags.length - a.tags.length);
                break;
            case 'taught_count-asc': // New case: Sort by taught_count in ascending order
                this.skills.sort((a, b) => a.taught_count - b.taught_count);
                break;
            case 'taught_count-desc': // New case: Sort by taught_count in descending order
                this.skills.sort((a, b) => b.taught_count - a.taught_count);
                break;
            case 'days-asc': // New case: Sort by days in ascending order
                this.skills.sort((a, b) => a.days - b.days);
                break;
            case 'days-desc': // New case: Sort by days in descending order
                this.skills.sort((a, b) => b.days - a.days);
                break;
            default:
                this.skills.sort((a, b) => a.points - b.points);
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
                                taught_count="${skill.taught_count}"
                                img-src="${skill.user.profile}"
                                userId="${skill.user.user_id}"
                                skillId="${skill.skill_id}"
                                class="hover:cursor-pointer">
                            </sk-ill>
                        `).join("")}
                    </div>
                </div>
            </div>
        `;
    }
}

customElements.define("skill-wrapper", SkillWrapper);