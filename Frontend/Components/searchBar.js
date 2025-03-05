class SearchBar extends HTMLElement {
  constructor() {
    super();
    this.selectedCategories = [];
    this.tags = []; 
    this.token = localStorage["JWT"];
    this.getRandomColor = () => {
      const colors = ['#BEFBFF', '#C8FFBE', '#FFF7BE', '#FFBEC8', '#BEC7FF'];
      return colors[Math.floor(Math.random() * colors.length)];
    };
    this.categories = [
      { id: '1', name: 'j', color: this.getRandomColor() },
      { id: '2', name: 'k', color: this.getRandomColor() },
    ];
    this.render();
    this.setupEventListeners();
  }

  async fetchSearchResults(keyword, tags) {
    try {
      const formattedTags = tags.join('+');
      const url = `http://localhost/skillSwap/skill-swap/search_page.php?keyword=${encodeURIComponent(keyword)}&tag=${encodeURIComponent(formattedTags)}`;
      console.log('Fetching from URL:', url);

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          "Authorization": `Bearer ${this.token}`
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const data = await response.json();
      console.log('Search results:', data);
      return data;
    } catch (error) {
      console.error('Error fetching search results:', error);
      throw error;
    }
  }

  async fetchTags() {
    try {
      const url = 'http://localhost/skillSwap/skill-swap/tag.php';
      console.log('Fetching tags from:', url);

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          "Authorization": `Bearer ${this.token}`
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      if (!data || !Array.isArray(data.tag)) {
        throw new Error('Invalid tags data received from the API');
      }

      console.log('Tags fetched:', data);
      return data;
    } catch (error) {
      console.error('Error fetching tags:', error);
      throw error;
    }
  }

  processTags(tagsData) {
    const uniqueTags = new Set();

    tagsData.tag.forEach(tag => {
      if (!tag.tag) return; // Skip if tag.tag is null or undefined

      try {
        // Remove extra quotes and parse the tag string
        const cleanedTagString = tag.tag.replace(/^"|"$/g, ''); // Remove leading/trailing quotes
        const parsedTags = JSON.parse(cleanedTagString); // Parse the cleaned string

        if (Array.isArray(parsedTags)) {
          // If it's an array, add each tag to the set
          parsedTags.forEach(t => uniqueTags.add(t));
        } else if (typeof parsedTags === 'string') {
          // If it's a string, split by commas and add each tag
          parsedTags.split(',').forEach(t => uniqueTags.add(t.trim()));
        } else {
          // If it's a single value, add it directly
          uniqueTags.add(parsedTags);
        }
      } catch (e) {
        // If parsing fails, treat it as a plain string and split by commas
        const cleanedTagString = tag.tag.replace(/^"|"$/g, ''); // Remove leading/trailing quotes
        cleanedTagString.split(',').forEach(t => uniqueTags.add(t.trim()));
      }
    });

    return Array.from(uniqueTags);
  }

  render() {
    const template = document.createElement('template');
    template.innerHTML = `
      <div class="w-full mx-auto relative">
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
    
    // Clear any existing content and append the template
    this.innerHTML = '';
    this.appendChild(template.content.cloneNode(true));
  }

  setupEventListeners() {
    const input = this.querySelector('input');
    const availableDiv = this.querySelector('#available');
    const searchForDiv = this.querySelector('#searchFor');
    const availableContainer = availableDiv.querySelector('.flex');

    const blurOverlay = document.createElement('div');
    blurOverlay.className = 'fixed inset-0 bg-black/20 backdrop-blur-sm z-10 hidden transition-all duration-300 ease-in-out opacity-0';
    this.prepend(blurOverlay);

    // Use both keydown and keyup for better compatibility
    input.addEventListener('keydown', async (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        
        const keyword = input.value.trim();
        console.log(keyword)
        if ((this.tags.length !== 0) || keyword) {
          console.log('Search initiated with keyword:', keyword);
          console.log('Current tags:', this.tags);
          try {
            const results = await this.fetchSearchResults(keyword, this.tags);
            localStorage.setItem("skillsData", JSON.stringify(results));
            console.log('Search results received:', results);
            window.location.href = "../Search/index.html";
          } catch (error) {
            console.error('Search failed:', error);
          }
        }
      }
    });

    input.addEventListener('keyup', async (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
      }
    });

    input.addEventListener('focus', async () => {
      blurOverlay.classList.remove('hidden');
      availableDiv.classList.remove('hidden');
      requestAnimationFrame(() => {
        blurOverlay.style.opacity = '1';
        availableDiv.style.opacity = '1';
        availableDiv.style.transform = 'scale(1)';
      });

      // Fetch tags from the API
      try {
        const tagsData = await this.fetchTags();
        const processedTags = this.processTags(tagsData);
        console.log('Processed tags:', processedTags);

        // Update the available categories with the fetched tags
        this.categories = processedTags.map((tag, index) => ({
          id: String(index + 1), // Generate unique IDs
          name: tag,
          color: this.getRandomColor(),
        }));

        // Render the updated available categories
        this.renderAvailableCategories();
      } catch (error) {
        console.error('Error handling tags:', error);
      }
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
            this.tags.push(category.name);
            console.log('Tag added:', category.name);
            console.log('Current tags:', this.tags);
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
        const category = this.categories.find(c => c.id === categoryId);
        button.style.transform = 'scale(0.95)';
        button.style.opacity = '0';
        setTimeout(() => {
          this.selectedCategories = this.selectedCategories.filter(c => c.id !== categoryId);
          if (category) {
            this.tags = this.tags.filter(tag => tag !== category.name);
            console.log('Tag removed:', category.name);
            console.log('Current tags:', this.tags);
          }
          this.renderSelectedCategories();
          this.renderAvailableCategories();
        }, 150);
      }
    });
  }

  renderSelectedCategories() {
    const searchForDiv = this.querySelector('#searchFor');
    if (!searchForDiv) return; // Ensure the element exists

    searchForDiv.innerHTML = this.selectedCategories.map(category => `
      <button
        data-id="${category.id}"
        class="px-3 py-1 text-sm rounded-full flex items-center gap-1 text-[#2F2F2F] hover:cursor-pointer transition-all duration-300 ease-in-out hover:shadow-sm transform hover:scale-105"
        style="opacity: 0; transform: scale(0.95); background-color: ${category.color};min-width: 50px"
      >
        ${category.name}
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 hover:text-red-500 remove-category transition-colors duration-200">
          <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
        </svg>
      </button>
    `).join('');

    requestAnimationFrame(() => {
      if (!this.isConnected) return; // Ensure the component is still connected
      searchForDiv.querySelectorAll('button').forEach(button => {
        button.style.opacity = '1';
        button.style.transform = 'scale(1)';
      });
    });
  }

  renderAvailableCategories() {
    const availableContainer = this.querySelector('#available .flex');
    if (!availableContainer) return; // Ensure the element exists

    const availableCategories = this.categories.filter(
      category => !this.selectedCategories.find(c => c.id === category.id)
    );

    availableContainer.innerHTML = availableCategories.map(category => `
      <button
        data-id="${category.id}"
        class="px-3 py-1 text-sm rounded-full transition-all duration-300 ease-in-out text-[#2F2F2F] transform hover:scale-105 hover:shadow-sm hover:cursor-pointer"
        style="opacity: 0; transform: scale(0.95); background-color: ${category.color};min-width: 50px"
      >   
        ${category.name}
      </button>
    `).join('');

    requestAnimationFrame(() => {
      if (!this.isConnected) return; // Ensure the component is still connected
      availableContainer.querySelectorAll('button').forEach(button => {
        button.style.opacity = '1';
        button.style.transform = 'scale(1)';
      });
    });
  }

  disconnectedCallback() {
    document.removeEventListener('click', this.handleClickOutside);
  }
}

// Make sure to define the custom element before using it
if (!customElements.get('search-bar')) {
  customElements.define('search-bar', SearchBar);
}