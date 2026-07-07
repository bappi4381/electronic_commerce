const c=window.productVariantConfig||{attributes:[],categoryAttributeMappings:[],initialVariantIndex:0};let p=c.initialVariantIndex;const l=c.attributes,m=c.categoryAttributeMappings;function s(n){const e=m.find(t=>t.id===n);if(!e)return l;const a=e.attribute_ids;return l.filter(t=>a.includes(t.id))}function v(n){const e=document.getElementById("category-attribute-tags");if(!e)return;const a=s(n);if(!a.length){e.innerHTML='<span class="text-xs text-slate-400">No category-specific attributes assigned yet.</span>';return}e.innerHTML=a.map(t=>`<span class="rounded-full bg-white border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">${t.name}</span>`).join("")}function b(n,e){const a=document.getElementById(`variant-${n}`);if(!a)return;let t=a.querySelector('[id^="variant-attrs-"]');t||(t=document.createElement("div"),a.appendChild(t));const r=e.map(o=>`
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">${o.name}</label>
                <div class="flex flex-wrap gap-2">
                    ${o.values.map(i=>`
                        <label class="cursor-pointer">
                            <input type="checkbox" name="variants[${n}][attribute_value_ids][]" value="${i.id}" class="sr-only peer">
                            <span class="peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary border border-slate-200 text-slate-600 text-xs font-bold px-3 py-1.5 rounded-lg transition-all hover:border-blue-300 select-none">
                                ${i.value}
                            </span>
                        </label>
                    `).join("")}
                </div>
            </div>
        `).join("");t.innerHTML=r,t.id=`variant-attrs-${n}`}function g(){const n=document.getElementById("category");if(!n)return;const e=parseInt(n.value,10);if(!e)return;const a=s(e);v(e),document.querySelectorAll(".variant-row").forEach(t=>{const r=t.id.replace("variant-","");b(r,a)})}function x(n){document.querySelectorAll(".tab-btn").forEach(t=>t.classList.remove("active")),document.querySelectorAll(".tab-content").forEach(t=>t.classList.remove("active"));const e=document.getElementById(`tab-${n}`),a=document.getElementById(`content-${n}`);e&&e.classList.add("active"),a&&a.classList.add("active")}function f(){const n=document.getElementById("variants-container"),e=document.getElementById("no-variants-msg");if(!n)return;e&&e.remove();const a=parseInt(document.getElementById("category").value,10),t=a?s(a):l,r=p++,o=t.map(d=>`
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">${d.name}</label>
                <div class="flex flex-wrap gap-2">
                    ${d.values.map(u=>`
                        <label class="cursor-pointer">
                            <input type="checkbox" name="variants[${r}][attribute_value_ids][]" value="${u.id}" class="sr-only peer">
                            <span class="peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary border border-slate-200 text-slate-600 text-xs font-bold px-3 py-1.5 rounded-lg transition-all hover:border-blue-300 select-none">
                                ${u.value}
                            </span>
                        </label>
                    `).join("")}
                </div>
            </div>
        `).join(""),i=document.createElement("div");i.className="variant-row p-5 space-y-3 border-t border-slate-100",i.id=`variant-${r}`,i.innerHTML=`
        <div class="flex items-center justify-between">
            <span class="text-xs font-black uppercase text-slate-500 tracking-widest">Variant #${r+1}</span>
            <button type="button" onclick="removeVariant(${r})" class="text-red-400 hover:text-red-600 transition-colors text-sm font-bold">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">SKU</label>
                <input type="text" name="variants[${r}][sku]" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold outline-none focus:border-blue-400 transition-all" placeholder="e.g. TSH-RED-M">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Price Override (৳)</label>
                <input type="number" name="variants[${r}][price]" min="0" step="0.01" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold outline-none focus:border-blue-400 transition-all" placeholder="Leave blank = base price">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Stock *</label>
                <input type="number" name="variants[${r}][stock]" min="0" value="0" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold outline-none focus:border-blue-400 transition-all">
            </div>
        </div>
        ${o}
    `,n.appendChild(i)}function y(n){const e=document.getElementById(`variant-${n}`);if(e&&e.remove(),!document.querySelectorAll(".variant-row").length){const a=document.getElementById("variants-container");a&&(a.innerHTML=`
                <div id="no-variants-msg" class="p-8 text-center text-slate-400 text-sm">
                    <i class="bi bi-boxes text-4xl block mb-2 opacity-30"></i>No variants added yet. Click "Add Variant" to start.
                </div>
            `)}}function w(n){const e=document.getElementById("image-preview");e&&(e.innerHTML="",Array.from(n.files).forEach(a=>{const t=new FileReader;t.onload=r=>{e.innerHTML+=`
                <div class="rounded-xl overflow-hidden border border-slate-200 aspect-square">
                    <img src="${r.target.result}" class="w-full h-full object-cover">
                </div>
            `},t.readAsDataURL(a)}))}window.getAttributesForCategory=s;window.onCategoryChange=g;window.switchTab=x;window.addVariant=f;window.removeVariant=y;window.previewImages=w;window.updateVariantAttributes=b;
