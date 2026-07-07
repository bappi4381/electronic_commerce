const config = window.productVariantConfig || {
    attributes: [],
    categoryAttributeMappings: [],
    initialVariantIndex: 0,
};

let variantIndex = config.initialVariantIndex;
const attributes = config.attributes;
const categoryAttributeMappings = config.categoryAttributeMappings;

function getAttributesForCategory(categoryId) {
    const mapping = categoryAttributeMappings.find((m) => m.id === categoryId);
    if (!mapping) {
        return attributes;
    }

    const attributeIds = mapping.attribute_ids;
    return attributes.filter((attr) => attributeIds.includes(attr.id));
}

function renderCategoryAttributeSummary(categoryId) {
    const tagContainer = document.getElementById('category-attribute-tags');
    if (!tagContainer) {
        return;
    }

    const applicableAttrs = getAttributesForCategory(categoryId);
    if (!applicableAttrs.length) {
        tagContainer.innerHTML = '<span class="text-xs text-slate-400">No category-specific attributes assigned yet.</span>';
        return;
    }

    tagContainer.innerHTML = applicableAttrs
        .map((attr) => `<span class="rounded-full bg-white border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">${attr.name}</span>`)
        .join('');
}

function updateVariantAttributes(idx, applicableAttrs) {
    const variantDiv = document.getElementById(`variant-${idx}`);
    if (!variantDiv) {
        return;
    }

    let attrContainer = variantDiv.querySelector('[id^="variant-attrs-"]');
    if (!attrContainer) {
        attrContainer = document.createElement('div');
        variantDiv.appendChild(attrContainer);
    }

    const attrHtml = applicableAttrs
        .map((attr) => `
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">${attr.name}</label>
                <div class="flex flex-wrap gap-2">
                    ${attr.values
                        .map(
                            (val) => `
                        <label class="cursor-pointer">
                            <input type="checkbox" name="variants[${idx}][attribute_value_ids][]" value="${val.id}" class="sr-only peer">
                            <span class="peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary border border-slate-200 text-slate-600 text-xs font-bold px-3 py-1.5 rounded-lg transition-all hover:border-blue-300 select-none">
                                ${val.value}
                            </span>
                        </label>
                    `
                        )
                        .join('')}
                </div>
            </div>
        `)
        .join('');

    attrContainer.innerHTML = attrHtml;
    attrContainer.id = `variant-attrs-${idx}`;
}

function onCategoryChange() {
    const categoryElement = document.getElementById('category');
    if (!categoryElement) {
        return;
    }

    const categoryId = parseInt(categoryElement.value, 10);
    if (!categoryId) {
        return;
    }

    const applicableAttrs = getAttributesForCategory(categoryId);
    renderCategoryAttributeSummary(categoryId);

    document.querySelectorAll('.variant-row').forEach((variantDiv) => {
        const idx = variantDiv.id.replace('variant-', '');
        updateVariantAttributes(idx, applicableAttrs);
    });
}

function switchTab(lang) {
    document.querySelectorAll('.tab-btn').forEach((button) => button.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach((content) => content.classList.remove('active'));

    const tabButton = document.getElementById(`tab-${lang}`);
    const tabContent = document.getElementById(`content-${lang}`);

    if (tabButton) {
        tabButton.classList.add('active');
    }
    if (tabContent) {
        tabContent.classList.add('active');
    }
}

function addVariant() {
    const container = document.getElementById('variants-container');
    const noMsg = document.getElementById('no-variants-msg');

    if (!container) {
        return;
    }

    if (noMsg) {
        noMsg.remove();
    }

    const categoryId = parseInt(document.getElementById('category').value, 10);
    const applicableAttrs = categoryId ? getAttributesForCategory(categoryId) : attributes;

    const idx = variantIndex++;
    const attrHtml = applicableAttrs
        .map((attr) => `
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">${attr.name}</label>
                <div class="flex flex-wrap gap-2">
                    ${attr.values
                        .map(
                            (val) => `
                        <label class="cursor-pointer">
                            <input type="checkbox" name="variants[${idx}][attribute_value_ids][]" value="${val.id}" class="sr-only peer">
                            <span class="peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary border border-slate-200 text-slate-600 text-xs font-bold px-3 py-1.5 rounded-lg transition-all hover:border-blue-300 select-none">
                                ${val.value}
                            </span>
                        </label>
                    `
                        )
                        .join('')}
                </div>
            </div>
        `)
        .join('');

    const div = document.createElement('div');
    div.className = 'variant-row p-5 space-y-3 border-t border-slate-100';
    div.id = `variant-${idx}`;
    div.innerHTML = `
        <div class="flex items-center justify-between">
            <span class="text-xs font-black uppercase text-slate-500 tracking-widest">Variant #${idx + 1}</span>
            <button type="button" onclick="removeVariant(${idx})" class="text-red-400 hover:text-red-600 transition-colors text-sm font-bold">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">SKU</label>
                <input type="text" name="variants[${idx}][sku]" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold outline-none focus:border-blue-400 transition-all" placeholder="e.g. TSH-RED-M">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Price Override (৳)</label>
                <input type="number" name="variants[${idx}][price]" min="0" step="0.01" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold outline-none focus:border-blue-400 transition-all" placeholder="Leave blank = base price">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Stock *</label>
                <input type="number" name="variants[${idx}][stock]" min="0" value="0" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold outline-none focus:border-blue-400 transition-all">
            </div>
        </div>
        ${attrHtml}
    `;

    container.appendChild(div);
}

function removeVariant(idx) {
    const element = document.getElementById(`variant-${idx}`);
    if (element) {
        element.remove();
    }

    if (!document.querySelectorAll('.variant-row').length) {
        const container = document.getElementById('variants-container');
        if (container) {
            container.innerHTML = `
                <div id="no-variants-msg" class="p-8 text-center text-slate-400 text-sm">
                    <i class="bi bi-boxes text-4xl block mb-2 opacity-30"></i>No variants added yet. Click "Add Variant" to start.
                </div>
            `;
        }
    }
}

function previewImages(input) {
    const preview = document.getElementById('image-preview');
    if (!preview) {
        return;
    }

    preview.innerHTML = '';
    Array.from(input.files).forEach((file) => {
        const reader = new FileReader();
        reader.onload = (event) => {
            preview.innerHTML += `
                <div class="rounded-xl overflow-hidden border border-slate-200 aspect-square">
                    <img src="${event.target.result}" class="w-full h-full object-cover">
                </div>
            `;
        };
        reader.readAsDataURL(file);
    });
}

window.getAttributesForCategory = getAttributesForCategory;
window.onCategoryChange = onCategoryChange;
window.switchTab = switchTab;
window.addVariant = addVariant;
window.removeVariant = removeVariant;
window.previewImages = previewImages;
window.updateVariantAttributes = updateVariantAttributes;
