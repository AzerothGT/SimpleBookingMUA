import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import './App.css'
import Home from './pages/user/Home'
import Services from './pages/user/Services'
import BookingPage from './pages/user/BookingPage'
import MyBookings from './pages/user/MyBookings'
import AdminDashboard from './pages/admin/AdminDashboard'
import ActivityLogsPage from './pages/admin/ActivityLogsPage'
import BookingsPage from './pages/admin/BookingsPage'
import ServicesPage from './pages/admin/ServicesPage'
import LoginPage from './pages/user/Login'

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/services" element={<Services />} />
        <Route path="/booking" element={<BookingPage />} />
        <Route path="/my-bookings" element={<MyBookings />} />
        <Route path="/admin" element={<AdminDashboard />} />
        <Route path="/admin/bookings" element={<BookingsPage />} />
        <Route path="/admin/services" element={<ServicesPage />} />
        <Route path="/admin/activity" element={<ActivityLogsPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  )
}
