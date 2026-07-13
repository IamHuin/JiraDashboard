<?php

namespace App\Services\NLTC;

use App\Repositories\Interfaces\JiraNltcInterface;
use App\Services\Dashboard\HandleSlsxUlnlRatioService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class JiraNltcService
{
    protected JiraNltcInterface $nltcRepository;

    public function __construct(JiraNltcInterface $nltcRepository)
    {
        $this->nltcRepository = $nltcRepository;
    }

    /**
     * Import data from excel file
     *
     * @param UploadedFile $file
     * @return array
     */
    public function importData(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        // Xóa các dòng trống
        $data = array_values(array_filter($data, function ($row) {
            foreach ($row as $cell) {
                if ($cell !== null && trim((string)$cell) !== '') {
                    return true;
                }
            }

            return false;
        }));

        if (empty($data)) {
            return [
                'success' => false,
                'message' => 'File không có dữ liệu.',
            ];
        }

        // Tìm dòng header
        $headerIndex = null;
        $period = null;
        foreach ($data as $index => $row) {
            if (isset($row[0]) && trim((string)$row[0]) === 'Dự án') {
                $headerIndex = $index;
            }

            if (isset($row[7]) && str_contains(trim((string)$row[7]), 'Từ: ')) {
                $period = Carbon::parse(str_replace('Từ: ', '', trim((string)$row[7])))->format('d-Y');
            }
        }

        if ($headerIndex === null || $period === null) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy dòng tiêu đề.',
            ];
        }

        $headers = array_map('trim', $data[$headerIndex]);

        $requiredColumns = [
            'Dự án',
            'Email',
            'Tên',
            'Role',
            'Level',
            'Ra Tiêu chuẩn',
        ];

        // Kiểm tra thiếu cột
        $missingColumns = array_diff($requiredColumns, $headers);

        if (!empty($missingColumns)) {
            return [
                'success' => false,
                'message' => 'File không đúng định dạng. Thiếu cột: ' . implode(', ', $missingColumns),
            ];
        }

        // Mapping tên cột => index
        $columnIndexes = [];

        foreach ($requiredColumns as $column) {
            $columnIndexes[$column] = array_search($column, $headers);
        }

        // Chỉ lấy dữ liệu phía dưới header
        $rows = array_slice($data, $headerIndex + 1);

        if (count($rows) == 0) {
            return [
                'success' => false,
                'message' => 'File không có dữ liệu để import.',
            ];
        }

        if (count($rows) > 500) {
            return [
                'success' => false,
                'message' => 'Vui lòng import tối đa 500 bản ghi.',
            ];
        }

        $uniqueData = [];

        foreach ($rows as $row) {

            $username = explode('@', trim((string)($row[$columnIndexes['Email']] ?? '')))[0];
            $standard = trim((string)($row[$columnIndexes['Ra Tiêu chuẩn']] ?? ''));
            
            if ($standard === '') {
                continue;
            }

            if ($username === '') {
                continue;
            }

            $uniqueData[] = [
                'period' => $period,
                'project_name' => trim((string)($row[$columnIndexes['Dự án']] ?? '')),
                'user_name' => $username,
                'display_name' => trim((string)($row[$columnIndexes['Tên']] ?? '')),
                'role' => trim((string)($row[$columnIndexes['Role']] ?? '')),
                'level' => strtoupper(trim((string)($row[$columnIndexes['Level']] ?? ''))),
                'standard' => $standard,
            ];
        }

        if (!empty($uniqueData)) {
            $this->nltcRepository->upsertData($uniqueData);

            // Tính toán tỷ lệ SLSX / NLTC sau khi import NLTC thành công
            $periods = array_unique(array_column($uniqueData, 'period'));
            $ratioService = app(HandleSlsxUlnlRatioService::class);
            $ratioService->calculateAndSaveRatios($periods, auth()->id());
        }

        return [
            'success' => true,
            'message' => 'Import thành công.',
        ];
    }
}
