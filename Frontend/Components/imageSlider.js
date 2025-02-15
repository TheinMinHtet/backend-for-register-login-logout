class ImageSlider extends HTMLElement {
    constructor() {
        super();
        this.currentSlide = 0;
        this.originalSkills = [
            { title: "Guitar", description: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut eu mollis tortor, sed posuere elit. Pellentesque sed imperdiet arcu, non interdum libero. Mauris non mi id enim volutpat efficitur. Donec quis eros at nunc maximus tristique. Nunc pretium risus magna, et vehicula leo tincidunt nec. Mauris mollis vehicula ante ac sollicitudin. Nullam non justo at purus accumsan aliquam.", tags: [{ text: "boy" }, { text: "girl" }] },
            { title: "Piano", description: "Mastering the piano notes.", tags: [{ text: "music" }, { text: "classic" }] },
            { title: "Coding", description: "Building amazing projects.", tags: [{ text: "developer" }, { text: "tech" }] },
            { title: "Photography", description: "Capturing the beauty of life.", tags: [{ text: "lens" }, { text: "nature" }] },
            { title: "Dancing", description: "Expressing with movement.", tags: [{ text: "hiphop" }, { text: "freestyle" }] }
        ];
    }

    connectedCallback() {
        this.render();
        this.attachEventListeners();
    }

    nextSlide() {
        const maxSlide = Math.ceil(this.originalSkills.length / 2) - 1;
        this.currentSlide = (this.currentSlide + 1) > maxSlide ? 0 : this.currentSlide + 1;
        this.updateSlide();
    }

    prevSlide() {
        const maxSlide = Math.ceil(this.originalSkills.length / 2) - 1;
        this.currentSlide = (this.currentSlide - 1) < 0 ? maxSlide : this.currentSlide - 1;
        this.updateSlide();
    }

    updateSlide() {
        const slider = this.querySelector('.slider-container');
        slider.style.transform = `translateX(-${this.currentSlide * 100}%)`;
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
        nextBtn.addEventListener('click', () => this.nextSlide());
        prevBtn.addEventListener('click', () => this.prevSlide());
    }

    render() {
        const numberOfPairs = Math.ceil(this.originalSkills.length / 2);
        const pairs = Array.from({ length: numberOfPairs }, (_, i) => {
            return this.originalSkills.slice(i * 2, i * 2 + 2);
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
                                        title="${skill.title}" 
                                        description="${skill.description}"
                                        color="#D3E8FB" 
                                        border="-18px -18px 36px rgba(255, 255, 255, 0.25), 18px 18px 36px rgba(0, 0, 0, 0.25)"
                                        tags='${JSON.stringify(skill.tags)}'>
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
    }
}

customElements.define('image-slider', ImageSlider);