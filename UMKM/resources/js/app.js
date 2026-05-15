import "./bootstrap";

document
    .querySelectorAll("form[data-realtime-submit='true']")
    .forEach((form) => {
        form.addEventListener("submit", async (event) => {
            if (event.defaultPrevented) {
                return;
            }

            event.preventDefault();

            if (form.dataset.submitting === "1") {
                return;
            }

            form.dataset.submitting = "1";
            const submitButton = form.querySelector("button[type='submit']");
            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const response = await fetch(form.action, {
                    method: (form.method || "POST").toUpperCase(),
                    body: new FormData(form),
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "text/html, application/json",
                    },
                });

                if (!response.ok) {
                    window.location.reload();
                    return;
                }

                if (form.dataset.resetOnSuccess === "true") {
                    form.reset();
                }
            } catch (_error) {
                window.location.reload();
            } finally {
                form.dataset.submitting = "0";
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    });

window.Echo.channel("stock-updates").listen("ProductSold", (event) => {
    console.log("ProductSold received:", event);

    const stockCell = document.querySelector(
        `[data-product-stock="${event.product_id}"]`,
    );

    const firstOption = document.querySelector(
        `[data-product-option="${event.product_id}"]`,
    );

    const optionCurrentStock = firstOption
        ? Number(firstOption.dataset.stockValue ?? 0)
        : 0;

    const currentStock = stockCell
        ? Number(
              stockCell.dataset.stockValue ??
                  stockCell.textContent.replace(/[^\d]/g, ""),
          ) || 0
        : optionCurrentStock;
    const nextStock = Math.max(currentStock - Number(event.qty_deducted), 0);

    if (stockCell) {
        stockCell.dataset.stockValue = String(nextStock);
        stockCell.textContent = nextStock.toLocaleString("id-ID");
    }

    document
        .querySelectorAll(`[data-product-option="${event.product_id}"]`)
        .forEach((option) => {
            const name = option.dataset.productName || "Produk";
            option.dataset.stockValue = String(nextStock);
            option.textContent = `${name} (Stok: ${nextStock.toLocaleString("id-ID")})`;
        });
});

// Listener untuk Material Stock Updates
window.Echo.channel("material-stock-updates").listen(
    "MaterialUsed",
    (event) => {
        console.log("MaterialUsed received:", event);

        const materialCell = document.querySelector(
            `[data-material-stock="${event.material_id}"]`,
        );

        if (!materialCell) {
            return;
        }

        const currentStock =
            Number(
                materialCell.dataset.stockValue ??
                    materialCell.textContent
                        .replace(/[^\d.,]/g, "")
                        .replace(",", "."),
            ) || 0;
        const nextStock = Math.max(
            currentStock - Number(event.quantity_used),
            0,
        );

        materialCell.dataset.stockValue = String(nextStock);
        materialCell.textContent = nextStock.toLocaleString("id-ID", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    },
);

// Listener untuk Production Status Updates
window.Echo.channel("production-status-updates").listen(
    "ProductionStatusUpdated",
    (event) => {
        console.log("ProductionStatusUpdated received:", event);

        const statusCell = document.querySelector(
            `[data-production-status="${event.production_id}"]`,
        );

        if (!statusCell) {
            return;
        }

        const statusMap = {
            process: { label: "Dalam Proses", class: "badge-info" },
            done: { label: "Selesai", class: "badge-success" },
            cancelled: { label: "Dibatalkan", class: "badge-danger" },
        };

        const statusInfo = statusMap[event.status] || {
            label: event.status,
            class: "badge-secondary",
        };

        statusCell.textContent = statusInfo.label;
        statusCell.className = `badge ${statusInfo.class}`;

        const doneForm = document.querySelector(
            `form[data-production-action="done"][data-production-id="${event.production_id}"]`,
        );
        const cancelledForm = document.querySelector(
            `form[data-production-action="cancelled"][data-production-id="${event.production_id}"]`,
        );

        if (event.status === "done") {
            doneForm?.remove();
        }

        if (event.status === "cancelled") {
            cancelledForm?.remove();
            doneForm?.remove();
        }

        if (event.status === "process") {
            // keep forms as-is for process; page render controls initial visibility
        }

        // Update quantity produced if available
        if (event.quantity_produced) {
            const qtyCell = document.querySelector(
                `[data-production-qty="${event.production_id}"]`,
            );
            if (qtyCell) {
                qtyCell.textContent = Number(
                    event.quantity_produced,
                ).toLocaleString("id-ID");
            }
        }
    },
);

// Listener untuk Stock Low Alerts
window.Echo.channel("stock-alerts").listen("StockLowAlert", (event) => {
    console.log("StockLowAlert received:", event);

    // Create notification element
    const notificationContainer =
        document.querySelector(".notifications-container") ||
        document.body.appendChild(document.createElement("div"));
    notificationContainer.className = "notifications-container";

    const alertDiv = document.createElement("div");
    alertDiv.className = "stock-alert-toast";
    alertDiv.role = "alert";
    alertDiv.style.cssText =
        "position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 320px; background: #fff7ed; color: #7c2d12; border: 1px solid #fdba74; border-radius: 12px; padding: 12px 14px; box-shadow: 0 10px 25px rgba(0,0,0,.12);";

    const itemType =
        event.item_type === "material" ? "Bahan Baku" : "Produk Jadi";

    alertDiv.innerHTML = `
        <div style="font-weight:700; margin-bottom: 4px;">Stock Low Alert</div>
        <div style="font-size: 12px; line-height: 1.4;">${itemType} ID ${event.product_id} stok sisa ${Number(event.current_stock).toLocaleString("id-ID")} unit.</div>
        <div style="font-size: 12px; line-height: 1.4;">Minimum threshold: ${Number(event.minimum_threshold).toLocaleString("id-ID")} unit.</div>
    `;

    notificationContainer.appendChild(alertDiv);

    // Auto-dismiss after 5 seconds
    setTimeout(() => alertDiv.remove(), 5000);
});

// Listener untuk Sales Analytics Updates
window.Echo.channel("sales-analytics").listen(
    "SalesAnalyticsUpdated",
    (event) => {
        console.log("SalesAnalyticsUpdated received:", event);

        // Update total sales
        const totalSalesEl = document.querySelector(
            "[data-analytics='total-sales']",
        );
        if (totalSalesEl) {
            totalSalesEl.textContent = Number(event.total_sales).toLocaleString(
                "id-ID",
            );
        }

        // Update total transactions
        const totalTransEl = document.querySelector(
            "[data-analytics='total-transactions']",
        );
        if (totalTransEl) {
            totalTransEl.textContent = Number(event.total_transactions);
        }

        // Update total revenue
        const totalRevEl = document.querySelector(
            "[data-analytics='total-revenue']",
        );
        if (totalRevEl) {
            totalRevEl.textContent = `Rp ${Number(event.total_revenue).toLocaleString("id-ID")}`;
        }

        // Update top products
        const topProductsList = document.querySelector(
            "[data-analytics='top-products']",
        );
        if (
            topProductsList &&
            event.top_products &&
            event.top_products.length
        ) {
            topProductsList.innerHTML = event.top_products
                .map(
                    (product) => `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${product.name}</span>
                    <span class="badge bg-primary rounded-pill">${product.qty} unit</span>
                </li>
            `,
                )
                .join("");
        }

        const historyTable = document.querySelector(
            "[data-sales-history-table='true']",
        );
        if (historyTable && event.latest_sale) {
            historyTable
                .querySelector("[data-sales-history-empty='true']")
                ?.remove();

            const csrfTokenInput = document.querySelector(
                "input[name='_token']",
            );
            const csrfToken = csrfTokenInput ? csrfTokenInput.value : "";

            const actionCell = event.latest_sale.can_delete
                ? `
                <form action="${event.latest_sale.destroy_url}" method="POST" onsubmit="return confirm('Hapus transaksi ini?')">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button class="px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 rounded-full transition-colors inline-flex items-center gap-1" type="submit">
                        <span class="material-symbols-outlined text-sm">delete</span>
                        <span>Hapus</span>
                    </button>
                </form>
            `
                : "";

            const row = document.createElement("tr");
            row.className = "hover:bg-primary/5 transition-colors group";
            row.innerHTML = `
                <td class="px-8 py-6 text-on-surface-variant">${event.latest_sale.time} WIB</td>
                <td class="px-8 py-6 font-bold text-teal-900">${event.latest_sale.product}</td>
                <td class="px-8 py-6 text-center">${Number(event.latest_sale.qty).toLocaleString("id-ID")}</td>
                <td class="px-8 py-6">${event.latest_sale.customer}</td>
                <td class="px-8 py-6">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full ${event.latest_sale.payment_dot_class}"></span>
                        ${event.latest_sale.payment_method}
                    </span>
                </td>
                <td class="px-8 py-6 text-right font-black">Rp ${Number(event.latest_sale.total).toLocaleString("id-ID")}</td>
                <td class="px-8 py-6">
                    <span class="px-3 py-1 ${event.latest_sale.status_class} rounded-full text-[10px] font-bold">${event.latest_sale.status_label}</span>
                </td>
                <td class="px-8 py-6 text-right">${actionCell}</td>
            `;

            historyTable.prepend(row);

            const rows = historyTable.querySelectorAll("tr");
            if (rows.length > 15) {
                rows[rows.length - 1].remove();
            }
        }
    },
);
