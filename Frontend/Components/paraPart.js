class ParaPart extends HTMLElement {
    static get observedAttributes() {
        return ["data"];
    }

    constructor() {
        super();
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === "data") {
            this.render();
        }
    }

    render() {
        // Get the data attribute
        const data = this.getAttribute("data");
        console.log("Data attribute:", data); // Debugging

        // Parse the data attribute (it should be a JSON string)
        let user = {};
        if (data) {
            try {
                const parsedData = JSON.parse(data);
                console.log("Parsed data:", parsedData); // Debugging

                // Access the first element of the `user` array
                if (parsedData) {
                    user = parsedData || {};
                }
                console.log("User object:", user); // Debugging
            } catch (error) {
                console.error("Error parsing data attribute:", error);
            }
        }

        // Render the component with the user data
        this.innerHTML = `
            <div class="w-full flex justify-center items-center pt-5 pb-20">
                <p class="w-[500px] text-center text-[20px] font-medium">
                    ${user.bio || ""}
                </p>
            </div>
        `;
    }
}

customElements.define("para-part", ParaPart);