# =============================================================================
#  export-stock.ps1
#  Exporte la vue [dbo].[V_BH_STGlob] de SQL Server vers data/stock.csv
#  SANS pilote ODBC et SANS extension PHP.
#
#  Utilise System.Data.SqlClient (intégré à Windows / .NET), qui parle
#  directement le protocole TDS à SQL Server. Rien à installer.
#
#  Utilisation (PowerShell, dans le dossier du projet) :
#     powershell -ExecutionPolicy Bypass -File outils\export-stock.ps1
#
#  Peut être planifié (Planificateur de tâches Windows) pour rafraîchir
#  automatiquement le CSV, par exemple chaque nuit.
# =============================================================================

param(
    [string]$Server   = "192.168.1.240",
    [string]$Database  = "SBO_AMA",       # <-- nom réel de la base SAP B1
    [string]$User      = "sa",
    [string]$Password  = "1AQWXCV",
    [string]$Out       = (Join-Path $PSScriptRoot "..\data\stock.csv")
)

$ErrorActionPreference = "Stop"

$query = @"
select [Magasin],
       [Item Code],
       [Item Name],
       [Disponible],
       [UoM],
       [CodeBars],
       [InActif],
       [Poids],
       [Price],
       [Value],
       [U_u_forcast],
       [U_Qte_Palette],
       [U_u_cat],
       [U_u_brand]
from [dbo].[V_BH_STGlob]
"@

$connStr = "Server=$Server;Database=$Database;User ID=$User;Password=$Password;" +
           "TrustServerCertificate=True;Encrypt=False"

try {
    $conn = New-Object System.Data.SqlClient.SqlConnection $connStr
    $conn.Open()

    $cmd            = $conn.CreateCommand()
    $cmd.CommandText = $query

    $adapter = New-Object System.Data.SqlClient.SqlDataAdapter $cmd
    $table   = New-Object System.Data.DataTable
    [void]$adapter.Fill($table)
}
finally {
    if ($conn -and $conn.State -eq 'Open') { $conn.Close() }
}

# Dossier de destination
$dir = Split-Path -Parent $Out
if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }

# Export CSV : séparateur « ; » (lu automatiquement par l'app), UTF-8.
$table | Export-Csv -Path $Out -NoTypeInformation -Delimiter ';' -Encoding UTF8

Write-Host ("Export termine : {0} ligne(s) ecrite(s) dans {1}" -f $table.Rows.Count, (Resolve-Path $Out))
