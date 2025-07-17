import { useState, useEffect } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import AdminLayout from '../components/AdminLayout'
import apiService from '../../../services/api'

// Pass currentUserId as a prop or get it from your auth context/store
function CreateReport({ currentUserId }) {
    const navigate = useNavigate()
    const [searchParams] = useSearchParams()
    const reportType = searchParams.get('type') || 'monthly'

    const [formData, setFormData] = useState({
        title: '',
        type: reportType,
        status: 'draft',
        year_id: '', // always string
        month_id: reportType === 'monthly' ? '' : '', // always string
        report_date: '',
        description: '',
        file: null
    })

    const [years, setYears] = useState([])
    const [months, setMonths] = useState([])
    const [errors, setErrors] = useState({})
    const [submitLoading, setSubmitLoading] = useState(false)
    const [selectedFile, setSelectedFile] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        fetchYearsAndMonths()
    }, [])

    useEffect(() => {
        generateDefaultTitle()
    }, [formData.type, formData.year_id, formData.month_id, years, months])

    const fetchYearsAndMonths = async () => {
        setLoading(true)
        try {
            const [yearsResult, monthsResult] = await Promise.all([
                apiService.getReportYears(),
                apiService.getReportMonths()
            ])
            // Always extract .data from API response
            const yearsArr = Array.isArray(yearsResult?.data) ? yearsResult.data : []
            const monthsArr = Array.isArray(monthsResult?.data) ? monthsResult.data : []
            setYears(yearsArr)
            setMonths(monthsArr)
            if (yearsArr.length > 0) {
                const currentYear = yearsArr.find(y => y.status === 'current') || yearsArr[0]
                setFormData(prev => ({ ...prev, year_id: String(currentYear.id) }))
            }
        } catch (error) {
            setYears([])
            setMonths([])
            console.error('Failed to fetch years/months:', error)
        } finally {
            setLoading(false)
        }
    }

    const generateDefaultTitle = () => {
        if (!formData.year_id || !years.length) return

        const year = years.find(y => String(y.id) === formData.year_id)
        if (!year) return

        let title = ''
        if (formData.type === 'monthly' && formData.month_id && months.length) {
            const month = months.find(m => String(m.id) === formData.month_id)
            if (month) {
                title = `Monthly Water Quality Report - ${month.name} ${year.year_value}`
            }
        } else if (formData.type === 'yearly') {
            title = `Annual Water Service Report ${year.year_value}`
        }

        if (title && (!formData.title || formData.title.startsWith('Monthly') || formData.title.startsWith('Annual'))) {
            setFormData(prev => ({ ...prev, title }))
        }
    }

    const validateFile = (file) => {
        const allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ]
        if (!allowedTypes.includes(file.type)) {
            throw new Error('Invalid file type. Allowed: PDF, DOC, DOCX, TXT')
        }
        if (file.size > 10 * 1024 * 1024) {
            throw new Error('File too large. Max 10MB')
        }
    }

    const handleFileSelect = (e) => {
        const file = e.target.files[0]
        if (file) {
            try {
                validateFile(file)
                setSelectedFile(file)
                setFormData(prev => ({ ...prev, file }))
                setErrors(prev => ({ ...prev, file: '' }))
            } catch (error) {
                setErrors(prev => ({ ...prev, file: error.message }))
                e.target.value = ''
                setSelectedFile(null)
                setFormData(prev => ({ ...prev, file: null }))
            }
        }
    }

    const handleChange = (e) => {
        const { name, value } = e.target;
        // Always store as string for select compatibility
        setFormData(prev => ({ ...prev, [name]: value }));
        if (errors[name]) {
            setErrors(prev => ({ ...prev, [name]: '' }));
        }
    }

    const validateForm = () => {
        const newErrors = {}
        if (!formData.title.trim()) newErrors.title = 'Title is required'
        if (!formData.type) newErrors.type = 'Report type is required'
        if (!formData.year_id) newErrors.year_id = 'Year is required'
        if (formData.type === 'monthly' && !formData.month_id) newErrors.month_id = 'Month is required'
        if (!formData.status) newErrors.status = 'Status is required'
        setErrors(newErrors)
        return Object.keys(newErrors).length === 0
    }

    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes'
        const k = 1024
        const sizes = ['Bytes', 'KB', 'MB', 'GB']
        const i = Math.floor(Math.log(bytes) / Math.log(k))
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateForm()) return;
        if (formData.type === 'monthly' && !formData.file) {
            setErrors(prev => ({ ...prev, file: 'Please select a file to upload.' }));
            return;
        }
        setSubmitLoading(true);
        try {
            const submitData = new FormData();
            // Always append required fields
            submitData.append('title', formData.title || '');
            submitData.append('type', formData.type || '');
            submitData.append('status', formData.status || '');
            submitData.append('year_id', formData.year_id || '');
            if (formData.type === 'monthly') {
                submitData.append('month_id', formData.month_id || '');
            }
            if (formData.description) submitData.append('description', formData.description);
            // Add created_by (replace with your actual user ID source if needed)
            submitData.append('created_by', currentUserId ? String(currentUserId) : '');

            const year = years.find(y => String(y.id) === formData.year_id);
            if (year) {
                let reportDate = '';
                if (formData.type === 'monthly' && formData.month_id) {
                    const lastDay = new Date(year.year_value, Number(formData.month_id), 0).getDate();
                    reportDate = `${year.year_value}-${String(formData.month_id).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
                } else {
                    reportDate = `${year.year_value}-12-31`;
                }
                submitData.append('report_date', reportDate);
            }

            if (formData.file) {
                submitData.append('file', formData.file);
            }

            let result;
            if (formData.type === 'monthly') {
                result = await apiService.createMonthlyReport(submitData);
            } else {
                result = await apiService.createYearlyReport(submitData);
            }

            if (result && result.error) {
                setErrors({ submit: result.error });
            } else {
                navigate('/admin/reports', {
                    state: { message: 'Report created successfully' }
                });
            }
        } catch (error) {
            let errorMsg = error?.response?.data?.message || error.message || 'Error submitting report';
            if (error?.response?.data?.errors) {
                const details = error.response.data.errors;
                errorMsg += ': ' + Object.values(details).flat().join(' ');
            }
            setErrors({ submit: errorMsg });
            console.error('Submit failed:', error);
        } finally {
            setSubmitLoading(false);
        }
    }

    return (
        <AdminLayout>
            <div className="max-w-4xl mx-auto px-4 py-8 space-y-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <button
                            onClick={() => navigate('/admin/reports')}
                            className="text-blue-600 hover:text-blue-800 font-medium text-sm"
                        >
                            ← Back to Reports
                        </button>
                        <h1 className="text-3xl font-bold text-gray-900 mt-2">📄 Create New Report</h1>
                        <p className="text-gray-600 text-sm">Fill in the details to create a new {reportType} report</p>
                    </div>
                </div>

                {/* Form */}
                <form
                    onSubmit={handleSubmit}
                    className="bg-white rounded-xl shadow-md border border-gray-100 p-8 space-y-6"
                >
                    {errors.submit && (
                        <div className="text-red-700 bg-red-100 border border-red-200 px-4 py-3 rounded-lg text-sm">
                            {errors.submit}
                        </div>
                    )}

                    {/* Type, Year, Month */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Report Type *</label>
                            <select
                                name="type"
                                value={formData.type}
                                onChange={handleChange}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="monthly">📅 Monthly Report</option>
                                <option value="yearly">🗓️ Yearly Report</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Year *</label>
                            <select
                                name="year_id"
                                value={formData.year_id}
                                onChange={handleChange}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                disabled={loading}
                            >
                                {loading ? (
                                    <option value="">Loading...</option>
                                ) : years.length === 0 ? (
                                    <option value="">No years available</option>
                                ) : (
                                    <>
                                        <option value="">Select Year</option>
                                        {years.map(y => (
                                            <option key={y.id} value={String(y.id)}>
                                                {y.year_value} {y.status === 'current' ? '(Current)' : ''}
                                            </option>
                                        ))}
                                    </>
                                )}
                            </select>
                        </div>

                        {formData.type === 'monthly' && (
                            <div className="md:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 mb-1">Month *</label>
                                <select
                                    name="month_id"
                                    value={formData.month_id || ''}
                                    onChange={handleChange}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    disabled={loading}
                                >
                                    {loading ? (
                                        <option value="">Loading...</option>
                                    ) : months.length === 0 ? (
                                        <option value="">No months available</option>
                                    ) : (
                                        <>
                                            <option value="">Select Month</option>
                                            {months.map(m => (
                                                <option key={m.id} value={String(m.id)}>{m.name}</option>
                                            ))}
                                        </>
                                    )}
                                </select>
                            </div>
                        )}
                    </div>

                    {/* Title */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                        <input
                            type="text"
                            name="title"
                            value={formData.title}
                            onChange={handleChange}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter report title"
                        />
                        {errors.title && <p className="text-sm text-red-600 mt-1">{errors.title}</p>}
                    </div>

                    {/* Description */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea
                            name="description"
                            value={formData.description}
                            onChange={handleChange}
                            rows="4"
                            placeholder="Enter description (optional)"
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    {/* Status and File Upload */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Status */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select
                                name="status"
                                value={formData.status}
                                onChange={handleChange}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="draft">📝 Draft</option>
                                <option value="published">✅ Published</option>
                            </select>
                        </div>

                        {/* File Upload */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Upload Report File</label>
                            <input
                                type="file"
                                onChange={handleFileSelect}
                                accept=".pdf,.doc,.docx,.txt"
                                className="block w-full text-sm text-gray-600 border border-gray-300 rounded-lg cursor-pointer bg-white file:mr-4 file:py-2 file:px-4 file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200"
                            />
                            {errors.file && <p className="text-sm text-red-600 mt-1">{errors.file}</p>}
                            <p className="text-sm text-gray-500 mt-1">Max 10MB – PDF, DOC, DOCX, TXT</p>
                        </div>
                    </div>

                    {/* Selected File Info */}
                    {selectedFile && (
                        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-700">
                            <h4 className="font-medium mb-2">📎 Selected File Info</h4>
                            <p><strong>Name:</strong> {selectedFile.name}</p>
                            <p><strong>Size:</strong> {formatFileSize(selectedFile.size)}</p>
                            <p><strong>Type:</strong> {selectedFile.type}</p>
                        </div>
                    )}

                    {/* Submit Buttons */}
                    <div className="flex justify-end gap-4 pt-6 border-t">
                        <button
                            type="button"
                            onClick={() => navigate('/admin/reports')}
                            className="px-5 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={submitLoading}
                            className="px-6 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 disabled:opacity-50"
                        >
                            {submitLoading ? (
                                <span className="flex items-center gap-2">
                                    <svg className="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24" fill="none">
                                        <circle className="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8v4a4 4 0 000 8v4a8 8 0 01-8-8z" />
                                    </svg>
                                    Creating...
                                </span>
                            ) : (
                                '📄 Create Report'
                            )}
                        </button>
                    </div>
                </form>
            </div>

        </AdminLayout>
    )
}

export default CreateReport
