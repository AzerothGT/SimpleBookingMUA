export default function AdminDataTable({ columns, rows, isLoading, error, emptyMessage = 'Belum ada data.', onRetry }) {
  if (isLoading) return <div className="admin-state">Memuat data...</div>
  if (error) return <div className="admin-state admin-state-error" role="alert"><span>{error}</span>{onRetry && <button className="admin-button admin-button-secondary" type="button" onClick={onRetry}>Coba lagi</button>}</div>
  if (!rows.length) return <div className="admin-state">{emptyMessage}</div>

  return <div className="admin-table-wrap"><table className="admin-table"><thead><tr>{columns.map((column) => <th key={column.key} className={column.className}>{column.label}</th>)}</tr></thead><tbody>{rows.map((row) => <tr key={row.id}>{columns.map((column) => <td key={column.key} className={column.className} data-label={column.label}>{column.render ? column.render(row) : row[column.key]}</td>)}</tr>)}</tbody></table></div>
}
