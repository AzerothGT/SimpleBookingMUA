# Booking Admin Toast Feedback Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ganti seluruh feedback operasi booking admin dari pesan inline menjadi toast yang sudah tersedia.

**Architecture:** `BookingsPage` mengambil fungsi `toast` dari `useToast` dan memanggilnya pada jalur sukses atau gagal setiap operasi. State dan markup `feedback` dihapus; error pemuatan tabel tetap memakai state `error` yang sudah ada.

**Tech Stack:** React 19, existing ToastContext, Vite, Oxlint

---

### Task 1: Integrate Toast Feedback

**Files:**
- Modify: `frontend-mua/src/pages/admin/BookingsPage.jsx:1-105`

- [ ] **Step 1: Import and initialize the existing toast hook**

Add:

```jsx
import { useToast } from '../../context/useToast'
```

Inside `BookingsPage`, add:

```jsx
const { toast } = useToast()
```

- [ ] **Step 2: Remove inline feedback state and reset calls**

Delete:

```jsx
const [feedback, setFeedback] = useState('')
```

Remove `setFeedback('')` from `openBooking` and `mutate`.

- [ ] **Step 3: Route operation outcomes through toast**

Use this success notification after an update:

```jsx
toast({
  type: 'success',
  title: 'Booking diperbarui',
  message: 'Booking dan jadwal berhasil diperbarui.',
})
```

Use this error notification in action `catch` blocks:

```jsx
toast({ type: 'error', message: getError(requestError) })
```

Keep `setError` for table loading errors because it represents persistent page state.

- [ ] **Step 4: Remove inline feedback rendering**

Stop passing `feedback` to `BookingDetail`, remove it from the component parameters, and delete:

```jsx
{feedback && <div className="admin-alert" role="status">{feedback}</div>}
```

- [ ] **Step 5: Validate the frontend**

Run: `npm --prefix frontend-mua run lint && npm --prefix frontend-mua run build`
Expected: Oxlint reports 0 errors and Vite completes the production build.

Automated interaction tests are not added because this frontend currently has no React DOM test runner; the change only connects the existing `useToast` API to existing action branches.
