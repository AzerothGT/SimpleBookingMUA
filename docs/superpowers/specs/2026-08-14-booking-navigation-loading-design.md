# Booking Navigation Loading State

## Goal

Show immediate loading feedback and prevent duplicate interactions when users navigate the booking form.

## Design

Add one `navigationLoading` boolean state in `BookingPage`. Navigation handlers set it before changing steps or quantities and clear it on the next animation frame. While active, navigation, quantity, and optional-detail controls are disabled. The primary next button shows `Memproses...`; the back button and quantity controls remain visually stable but cannot be clicked again.

Submit loading remains controlled by existing `submitState` and is unchanged.

## Behavior

- `Lanjut` starts navigation loading, validates first, then advances when valid.
- `Kembali` starts navigation loading and moves back one step.
- Quantity plus/minus starts navigation loading and applies the quantity update.
- Optional-detail toggle starts navigation loading and toggles its panel.
- Validation errors do not start navigation loading.
- Controls re-enable after the state transition is scheduled.

## Validation

Run `npm run build` and `npm run lint` from `frontend-mua`.
