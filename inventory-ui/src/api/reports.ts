import api from './index'

export const reportsApi = {
    // Inventaris
    inventoryExcel: (params?: { warehouse_id?: number }) =>
        api.get('/reports/inventory/excel', {
            params,
            responseType: 'blob', // penting untuk file download
        }),
    dashboardPdf: () =>
        api.get('/reports/dashboard/pdf', { responseType: 'blob' }),

    inventoryPdf: (params?: { warehouse_id?: number }) =>
        api.get('/reports/inventory/pdf', {
            params,
            responseType: 'blob',
        }),

    // Penjualan
    salesExcel: (params?: { from?: string; to?: string; status?: string }) =>
        api.get('/reports/sales/excel', {
            params,
            responseType: 'blob',
        }),

    salesPdf: (params?: { from?: string; to?: string; status?: string }) =>
        api.get('/reports/sales/pdf', {
            params,
            responseType: 'blob',
        }),
}

// Helper: trigger download dari blob response
export function downloadBlob(blob: Blob, filename: string) {
    const url  = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href  = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
}