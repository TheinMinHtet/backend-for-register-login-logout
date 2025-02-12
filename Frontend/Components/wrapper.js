class wrapper extends HTMLElement {
    constructor() {
        super()
       



        const shadow = this.attachShadow({ mode: 'open' });
       
       

        // Create a link to Tailwind CSS
        const linkElem = document.createElement('link');
        linkElem.setAttribute('rel', 'stylesheet');
        linkElem.setAttribute('href', '../output.css');

        // Create the container with Tailwind classes
        const container = document.createElement('div');
        container.setAttribute('class', `w-full roboto-condensed-font bg-[#F1F5F9] px-[5%] min-h-[100vh] h-full pt-[80px]`);
        container.innerHTML = `
        
        <slot></slot>
        `;


        // Append the Tailwind stylesheet and content to shadow DOM
        shadow.appendChild(linkElem);
        shadow.appendChild(container);

        

    }

    
}

customElements.define("wrap-per", wrapper);


