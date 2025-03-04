class ImageSlider extends HTMLElement {
    static get observedAttributes() {
        return ["skills"];
    }

    constructor() {
        super();
        this.currentSlide = 0;
        this.skills = []; // Initialize skills as an empty array
    }

    connectedCallback() {
        this.render();
        this.attachEventListeners();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === "skills" && newValue) {
            this.skills = JSON.parse(newValue); // Parse the skills attribute
            this.render();
        }
    }

    nextSlide() {
        const maxSlide = Math.ceil(this.skills.length / 2) - 1;
        this.currentSlide = (this.currentSlide + 1) > maxSlide ? 0 : this.currentSlide + 1;
        this.updateSlide();
    }

    prevSlide() {
        const maxSlide = Math.ceil(this.skills.length / 2) - 1;
        this.currentSlide = (this.currentSlide - 1) < 0 ? maxSlide : this.currentSlide - 1;
        this.updateSlide();
    }

    updateSlide() {
        const slider = this.querySelector('.slider-container');
        if (slider) {
            slider.style.transform = `translateX(-${this.currentSlide * 100}%)`;
        }
        // Update dots
        const dots = this.querySelectorAll('.dot');
        dots.forEach((dot, index) => {
            dot.classList.toggle('bg-white', index === this.currentSlide);
            dot.classList.toggle('bg-white/50', index !== this.currentSlide);
        });
    }

    attachEventListeners() {
        const nextBtn = this.querySelector('.next-btn');
        const prevBtn = this.querySelector('.prev-btn');
        if (nextBtn) nextBtn.addEventListener('click', () => this.nextSlide());
        if (prevBtn) prevBtn.addEventListener('click', () => this.prevSlide());
    }

    render() {
        console.log("ppp",this.profileImg)
        if (this.skills.length === 0) {
            // Display a fallback message if the skills array is empty
            this.innerHTML = `
                <div class="w-full h-[530px] flex justify-center items-center">
                    <p class="text-2xl text-[#2F2F2F]">No skills available. Add some skills to get started!</p>
                </div>
            `;
            return; // Exit the render method early
        }

        const numberOfPairs = Math.ceil(this.skills.length / 2);
        const pairs = Array.from({ length: numberOfPairs }, (_, i) => {
            return this.skills.slice(i * 2, i * 2 + 2);
        });

        this.innerHTML = `
            <div class="relative w-full h-[530px] overflow-hidden group pt-16 ps-8">
                <!-- Navigation Buttons -->
                <button class="prev-btn absolute left-4 top-1/2 -translate-y-1/2 z-10 bg-white/80 hover:bg-white rounded-full p-2 shadow-lg transition-all opacity-0 group-hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </button>
                
                <button class="next-btn absolute right-4 top-1/2 -translate-y-1/2 z-10 bg-white/80 hover:bg-white rounded-full p-2 shadow-lg transition-all opacity-0 group-hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>

                <!-- Slider Container -->
                <div class="slider-container flex transition-transform duration-300 ease-in-out h-full">
                    ${pairs.map(pair => `
                        <div class="min-w-full h-full flex gap-4">
                            ${pair.map(skill => `
                                <div class="w-1/2 flex justify-center">
                                    <sk-ill 
                                        title="${skill.name}" 
                                        description="${skill.description}"
                                        tags='${JSON.stringify(skill.tags)}'
                                        color="d3e8fb"
                                        skillId="${skill.skill_id}"
                                        userId="${localStorage.getItem('user_id')}"
                                        days="${skill.hours}"
                                taught_count="${skill.skill_taught}"
                                        img-src="${localStorage.getItem('profileImg')}"
                                        border="-18px -18px 36px rgba(255, 255, 255, 0.25), 18px 18px 36px rgba(0, 0, 0, 0.25)"
                                        >
                                    </sk-ill>
                                </div>
                            `).join('')}
                        </div>
                    `).join('')}
                </div>

                <!-- Dots Indicator -->
                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 flex gap-2">
                    ${pairs.map((_, index) => `
                        <div class="dot w-2 h-2 rounded-full ${index === this.currentSlide ? 'bg-white' : 'bg-white/50'}"></div>
                    `).join('')}
                </div>
            </div>
        `;

        this.attachEventListeners(); // Re-attach event listeners after rendering
    }
}

customElements.define('image-slider', ImageSlider);