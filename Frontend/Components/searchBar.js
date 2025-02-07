class SearchBar extends HTMLElement {
    constructor() {
      super();
      this.selectedCategories = [];
      this.getRandomColor = () => {
        const colors = ['#BEFBFF', '#C8FFBE', '#FFF7BE', '#FFBEC7', '#BEC7FF'];
      return colors[Math.floor(Math.random() * colors.length)];

      };
      this.categories = [
        { id: '1', name: 'Music',color: this.getRandomColor()  },
        { id: '2', name: 'Law', color: this.getRandomColor() },
      ];
      this.render();
      this.setupEventListeners();
    }
  
   
    
  
    render() {
      this.innerHTML = `
        <div class="w-full mx-auto relative  ">
          <div class="relative z-20">
            <div class="bg-[#D3E4FD] rounded-full h-12 flex items-center px-4 transition-all duration-300 ease-in-out transform hover:shadow-[0px_6px_10px_rgba(0,_0,_0,_0.25),_0px_6px_8px_#C4D3E0,_8px_-4px_6px_#C4D3E0]">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-500 mr-2 transition-transform duration-300 ease-in-out">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
              </svg>
              <div class="flex gap-2 flex-wrap transition-all duration-300 ease-in-out" id="searchFor"></div>
              <input
                type="text"
                class="flex-1 bg-transparent border-none outline-none placeholder:text-gray-500 text-gray-700 ml-2 transition-all duration-300 ease-in-out focus:placeholder:opacity-70"
                placeholder="Search"
              />
            </div>
            <div id="available" class="hidden absolute top-full left-0 right-0 mt-4 bg-white rounded-2xl shadow-lg border border-gray-100 p-6 z-20 transition-all duration-300 ease-in-out transform origin-top opacity-0 scale-95">
              <div class="flex flex-wrap gap-2"></div>
            </div>
          </div>
        </div>
      `;
    }
  
    setupEventListeners() {
      const input = this.querySelector('input');
      const availableDiv = this.querySelector('#available');
      const searchForDiv = this.querySelector('#searchFor');
      const availableContainer = availableDiv.querySelector('.flex');
  
      const blurOverlay = document.createElement('div');
      blurOverlay.className = 'fixed inset-0 bg-black/20 backdrop-blur-sm z-10 hidden transition-all duration-300 ease-in-out opacity-0';
      this.prepend(blurOverlay);
  
      input.addEventListener('focus', () => {
        blurOverlay.classList.remove('hidden');
        availableDiv.classList.remove('hidden');
        requestAnimationFrame(() => {
          blurOverlay.style.opacity = '1';
          availableDiv.style.opacity = '1';
          availableDiv.style.transform = 'scale(1)';
        });
      });
  
      blurOverlay.addEventListener('click', () => {
        blurOverlay.style.opacity = '0';
        availableDiv.style.opacity = '0';
        availableDiv.style.transform = 'scale(0.95)';
        setTimeout(() => {
          blurOverlay.classList.add('hidden');
          availableDiv.classList.add('hidden');
        }, 300);
      });
  
      this.renderAvailableCategories();
  
      availableContainer.addEventListener('click', (e) => {
        const button = e.target.closest('button');
        if (button) {
          const categoryId = button.dataset.id;
          const category = this.categories.find(c => c.id === categoryId);
          if (category && !this.selectedCategories.find(c => c.id === categoryId)) {
            button.style.transform = 'scale(0.95)';
            button.style.opacity = '0';
            setTimeout(() => {
              this.selectedCategories.push(category);
              this.renderSelectedCategories();
              this.renderAvailableCategories();
            }, 150);
          }
        }
      });
  
      searchForDiv.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-category')) {
          const button = e.target.closest('button');
          const categoryId = button.dataset.id;
          button.style.transform = 'scale(0.95)';
          button.style.opacity = '0';
          setTimeout(() => {
            this.selectedCategories = this.selectedCategories.filter(c => c.id !== categoryId);
            this.renderSelectedCategories();
            this.renderAvailableCategories();
          }, 150);
        }
      });
    }
  
    renderSelectedCategories() {
      const searchForDiv = this.querySelector('#searchFor');
      searchForDiv.innerHTML = this.selectedCategories.map(category => {
        const randomColor = this.getRandomColor();
        return `
          <button
            data-id="${category.id}"
            class="px-3 py-1 text-sm rounded-full flex items-center gap-1 text-[#2F2F2F] hover:cursor-pointer transition-all duration-300 ease-in-out hover:shadow-sm transform hover:scale-105"
            style="opacity: 0; transform: scale(0.95); background-color: ${category.color};"
          >
            ${category.name}
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 hover:text-red-500 remove-category transition-colors duration-200">
              <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
            </svg>
          </button>
        `;
      }).join('');
  
      requestAnimationFrame(() => {
        searchForDiv.querySelectorAll('button').forEach(button => {
          button.style.opacity = '1';
          button.style.transform = 'scale(1)';
        });
      });
    }
  
    renderAvailableCategories() {
      const availableContainer = this.querySelector('#available .flex');
      const availableCategories = this.categories.filter(
        category => !this.selectedCategories.find(c => c.id === category.id)
      );
  
      availableContainer.innerHTML = availableCategories.map(category => {
        const randomColor = this.getRandomColor();
        return `
          <button
            data-id="${category.id}"
            class="px-3 py-1 text-sm rounded-full transition-all duration-300 ease-in-out text-[#2F2F2F] transform hover:scale-105 hover:shadow-sm hover:cursor-pointer"
            style="opacity: 0; transform: scale(0.95); background-color: ${category.color};"
          >   
            ${category.name}
          </button>
        `;
      }).join('');
  
      requestAnimationFrame(() => {
        availableContainer.querySelectorAll('button').forEach(button => {
          button.style.opacity = '1';
          button.style.transform = 'scale(1)';
        });
      });
    }
  }
  
  customElements.define('search-bar', SearchBar);
  