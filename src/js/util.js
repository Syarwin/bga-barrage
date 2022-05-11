export const addActionButton = (id, label, callback, color = "blue") => {
    const html = `
        <div id="${id}" class="bgabutton bgabutton_${color}">${label}</div>
    `;

    document.getElementById("generalactions").insertAdjacentHTML("beforeend", html);

    document.getElementById(id).addEventListener("click", callback);

}