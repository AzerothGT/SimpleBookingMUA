import { lazy, Suspense } from 'react'
import { BrowserRouter, Navigate, Outlet, Route, Routes } from 'react-router-dom'
import './App.css'
import Home from './pages/user/Home'
import Services from './pages/user/Services'
import BookingPage from './pages/user/BookingPage'
import PaymentPage from './pages/user/PaymentPage'
import LoginPage from './pages/user/Login'
import { getStoredSession, hasValidSession } from './utils/session'

const AdminDashboard = lazy(() => import('./pages/admin/AdminDashboard'))
const ActivityLogsPage = lazy(() => import('./pages/admin/ActivityLogsPage'))
const BookingsPage = lazy(() => import('./pages/admin/BookingsPage'))
const ServicesPage = lazy(() => import('./pages/admin/ServicesPage'))
const UsersPage = lazy(() => import('./pages/admin/UsersPage'))

function RequireAuth() {
  if (!hasValidSession()) return <Navigate to="/login" replace />
  return <Outlet />
}

function RequireRole({ allow }) {
  const role = getStoredSession()?.user?.role
  if (!allow.includes(role)) return <Navigate to="/admin" replace />
  return <Outlet />
}

export default function App() {
  return (
    <BrowserRouter>
      <Suspense fallback={<div className="admin-state">Memuat...</div>}>
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/services" element={<Services />} />
          <Route path="/booking" element={<BookingPage />} />
          <Route path="/payment" element={<PaymentPage />} />

          <Route path="/login" element={<LoginPage />} />
          <Route element={<RequireAuth />}>
            <Route path="/admin" element={<AdminDashboard />} />
            <Route path="/admin/bookings" element={<BookingsPage />} />
            <Route path="/admin/services" element={<ServicesPage />} />
            <Route element={<RequireRole allow={['admin']} />}>
              <Route path="/admin/users" element={<UsersPage />} />
              <Route path="/admin/activity" element={<ActivityLogsPage />} />
            </Route>
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
    </BrowserRouter>
  )
}
