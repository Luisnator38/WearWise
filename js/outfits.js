// Datos de prueba (simulando respuesta de API)
const mockOutfits = [
    {
        id: 1,
        name: "Outfit Casual de Verano",
        category: "casual",
        clothes: [
            { id: 1, name: "Camiseta Blanca", type: "Camiseta", imageUrl: "https://action.com/hostedassets/CMSArticleImages/73/78/2566968_8720195984863-110_01_20230822155620.png?width=750&quality=75" },
            { id: 2, name: "Shorts Denim", type: "Pantalón", imageUrl: "https://www.vilebrequin.com/dw/image/v2/BBRG_PRD/on/demandware.static/-/Sites-vilebrequin-catalog-master/default/dwf3418ec5/images/GRNAV413-361/GRNAV413-361-front-3920x3920.png?sw=316&sh=316&sm=fit" },
            { id: 3, name: "Sandalias", type: "Zapatos", imageUrl: "https://images.footlocker.com/is/image/FLEU/314626902804?wid=250&hei=250" }
        ]
    },
    {
        id: 2,
        name: "Conjunto Formal Elegante",
        category: "formal",
        clothes: [
            { id: 4, name: "Camisa Blanca", type: "Camisa", imageUrl: "https://uniformesroger.com/WebRoot/Store/Shops/UniformesRoger/6585/66A4/A415/33CD/E96D/2E10/8536/6BAF/925141131.png" },
            { id: 5, name: "Pantalón Negro", type: "Pantalón", imageUrl: "https://www.regalospublicitarios.com/recursos/TopTex/img/descriptivas/TTNS736/620495.jpg" },
            { id: 6, name: "Zapatos de Vestir", type: "Zapatos", imageUrl: "https://d1fufvy4xao6k9.cloudfront.net/images/landings/446/oxfords-women.png" }
        ]
    },
    {
        id: 3,
        name: "Outfit Deportivo",
        category: "deportivo",
        clothes: [
            { id: 7, name: "Sudadera", type: "Abrigo", imageUrl: "https://tienda.austral.es/teresianas-tarragona/img/p/1/0/9/8/6/10986-large_default.jpg" },
            { id: 8, name: "Pantalón Deportivo", type: "Pantalón", imageUrl: "https://media.boohoo.com/i/boohoo/pzz61201_black_xl_4/mujer-pantal%C3%B3n-deportivo-plus-b%C3%A1sico-con-botamanga" },
            { id: 9, name: "Zapatillas Running", type: "Zapatos", imageUrl: "https://e00-elmundo.uecdn.es/assets/multimedia/imagenes/2024/03/11/17101429857811.jpg" }
        ]
    }
];

// Clases para categorías
const categoryClasses = {
    casual: "bg-info",
    formal: "bg-warning",
    deportivo: "bg-success"
};

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    loadOutfits();
    setupEventListeners();
});

// Cargar outfits
function loadOutfits(category = 'all') {
    const gallery = document.getElementById('outfitGallery');
    gallery.innerHTML = '';

    const filteredOutfits = category === 'all' 
        ? mockOutfits 
        : mockOutfits.filter(outfit => outfit.category === category);

    if (filteredOutfits.length === 0) {
        gallery.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-tshirt fa-4x mb-3 text-muted"></i>
                <h4 class="text-muted">No hay outfits en esta categoría</h4>
                <button class="btn btn-primary mt-3" id="newOutfitEmptyBtn">
                    <i class="fas fa-plus me-1"></i> Crear mi primer outfit
                </button>
            </div>
        `;
        document.getElementById('newOutfitEmptyBtn').addEventListener('click', () => {
            window.location.href = 'uploader.html';
        });
        return;
    }

    filteredOutfits.forEach(outfit => {
        const col = document.createElement('div');
        col.className = 'col-md-6 col-lg-4 mb-4';
        col.innerHTML = `
            <div class="outfit-card h-100" data-id="${outfit.id}">
                <div class="outfit-header">
                    <i class="fas ${getCategoryIcon(outfit.category)}"></i> ${outfit.name}
                </div>
                <div class="outfit-item">
                    <div class="outfit-item-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="outfit-item-content">
                        <div class="outfit-item-title">${outfit.clothes.length} prendas</div>
                        <div class="outfit-item-subtitle">${getCategoryName(outfit.category)}</div>
                    </div>
                </div>
                <div class="outfit-item">
                    <div class="outfit-item-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="outfit-item-content">
                        <button class="btn btn-sm btn-outline-primary view-outfit-btn w-100">
                            Ver detalles
                        </button>
                    </div>
                </div>
            </div>
        `;
        gallery.appendChild(col);
    });

    // Asignar eventos a los botones
    document.querySelectorAll('.view-outfit-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const outfitId = parseInt(this.closest('.outfit-card').getAttribute('data-id'));
            viewOutfit(outfitId);
        });
    });
}

function viewOutfit(outfitId) {
    const outfit = mockOutfits.find(o => o.id === outfitId);
    if (!outfit) return;

    const modal = new bootstrap.Modal(document.getElementById('outfitModal'));
    const modalElement = document.getElementById('outfitModal');
    
    // Limpiar el modal antes de mostrar nuevos datos
    const preview = document.getElementById('outfitPreview');
    const clothesList = document.getElementById('outfitClothes');
    preview.innerHTML = '';
    clothesList.innerHTML = '';
    
    // Eliminar contenedor de imagen principal si existe
    const existingMainContainer = modalElement.querySelector('.main-image-container');
    if (existingMainContainer) {
        existingMainContainer.remove();
    }

    document.getElementById('outfitModalTitle').textContent = outfit.name;

    // Crear contenedor para imagen principal
    const mainImageContainer = document.createElement('div');
    mainImageContainer.className = 'main-image-container';
    
    const mainImage = document.createElement('img');
    mainImage.alt = "Imagen principal del outfit";
    mainImageContainer.appendChild(mainImage);
    
    // Insertar el contenedor principal antes del preview
    preview.parentNode.insertBefore(mainImageContainer, preview);

    // Mostrar la primera imagen como principal por defecto
    if (outfit.clothes.length > 0) {
        mainImage.src = outfit.clothes[0].imageUrl;
    }

    // Mostrar miniaturas de todas las prendas
    outfit.clothes.forEach((clothe, index) => {
        const item = document.createElement('div');
        item.className = `clothing-item ${index === 0 ? 'selected' : ''}`;
        
        const img = document.createElement('img');
        img.src = clothe.imageUrl;
        img.alt = clothe.name;
        
        img.addEventListener('click', () => {
            // Cambiar la imagen principal
            mainImage.src = clothe.imageUrl;
            
            // Quitar selección de todas las imágenes
            document.querySelectorAll('#outfitModal .clothing-item').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Añadir selección a la imagen clickeada
            item.classList.add('selected');
        });
        
        item.appendChild(img);
        preview.appendChild(item);
    });

    // Mostrar lista de prendas
    outfit.clothes.forEach(clothe => {
        const listItem = document.createElement('div');
        listItem.className = 'clothing-list-item';
        
        const img = document.createElement('img');
        img.src = clothe.imageUrl;
        img.alt = clothe.name;
        img.className = 'thumb';
        
        const textContainer = document.createElement('div');
        textContainer.innerHTML = `
            <h6 class="mb-0">${clothe.name}</h6>
            <small class="text-muted">${clothe.type}</small>
        `;
        
        listItem.appendChild(img);
        listItem.appendChild(textContainer);
        clothesList.appendChild(listItem);
        
        // Hacer que las imágenes en la lista también cambien la principal
        img.addEventListener('click', () => {
            mainImage.src = clothe.imageUrl;
            
            // Actualizar selección en el preview
            document.querySelectorAll('#outfitModal .clothing-item').forEach((el, idx) => {
                el.classList.toggle('selected', outfit.clothes[idx] === clothe);
            });
        });
    });

    modal.show();
}

// Configurar event listeners
function setupEventListeners() {
    // Filtros
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const category = this.getAttribute('data-category');
            loadOutfits(category);
        });
    });
}

// Helper para nombres de categoría
function getCategoryName(category) {
    const names = {
        'casual': 'Casual',
        'formal': 'Formal',
        'deportivo': 'Deportivo'
    };
    return names[category] || 'Otro';
}

// Helper para iconos de categoría
function getCategoryIcon(category) {
    const icons = {
        'casual': 'fa-tshirt',
        'formal': 'fa-user-tie',
        'deportivo': 'fa-running'
    };
    return icons[category] || 'fa-tshirt';
}