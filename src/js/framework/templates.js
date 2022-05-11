export const tooltip = (infoTemplate, imageTemplate) => {
    
    return `
        <div class="tooltip complex">
            <div class="info">
                ${infoTemplate}
            </div>
            <div class="image">
                ${imageTemplate}
            </div>
        </div>
    `
};