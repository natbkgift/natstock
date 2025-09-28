<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สรุปแจ้งเตือนสินค้าประจำวัน</title>
</head>
<body style="font-family: 'Sarabun', 'Prompt', sans-serif; color:#333;">
    <h2>สรุปแจ้งเตือนสินค้าประจำวัน ({{ $thaiDate }})</h2>
    <p>สวัสดีค่ะ/ครับ ทีมงาน</p>
    @if(!empty($isTest))
        <p style="color:#d58512;">*** ข้อความนี้เป็นการทดสอบการแจ้งเตือนจากระบบ ***</p>
    @endif
    <p>นี่คือสรุปสินค้าที่ใกล้หมดอายุและสต็อกต่ำล่าสุดจากระบบคลังสินค้า:</p>

    <h3>สินค้าใกล้หมดอายุ</h3>
    <ul>
        @forelse($summary['expiring'] as $bucket)
            <li>
                ภายใน {{ $bucket['days'] }} วัน: {{ $bucket['count'] }} รายการ
                @if(!empty($bucket['items']))
                    <ul>
                        @foreach($bucket['items'] as $item)
                            <li>{{ $item['sku'] }} - {{ $item['name'] }} (หมดอายุ {{ $item['expire_date_thai'] }})</li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @empty
            <li>ไม่มีรายการสินค้าใกล้หมดอายุ</li>
        @endforelse
    </ul>

    <h3>สินค้าสต็อกต่ำ</h3>
    @if($summary['low_stock']['enabled'])
        <p>จำนวนทั้งหมด: {{ $summary['low_stock']['count'] }} รายการ</p>
        @if(!empty($summary['low_stock']['items']))
            <ul>
                @foreach($summary['low_stock']['items'] as $item)
                    <li>{{ $item['sku'] }} - {{ $item['name'] }} (คงเหลือ {{ $item['qty'] }} / จุดสั่งซื้อ {{ $item['reorder_point'] }})</li>
                @endforeach
            </ul>
        @else
            <p>ยังไม่มีรายการที่สต็อกต่ำในตอนนี้</p>
        @endif
    @else
        <p>ไม่ได้เปิดใช้งานการแจ้งเตือนสินค้าสต็อกต่ำ</p>
    @endif

    <p>
        สามารถดูรายละเอียดเพิ่มเติมได้ที่:
        <br>
        - รายงานสินค้าใกล้หมดอายุ: <a href="{{ $appUrl ? $appUrl.'/admin/reports/expiring' : '#' }}">{{ $appUrl ? $appUrl.'/admin/reports/expiring' : 'รายงานสินค้าใกล้หมดอายุ' }}</a><br>
        - รายงานสินค้าสต็อกต่ำ: <a href="{{ $appUrl ? $appUrl.'/admin/reports/low-stock' : '#' }}">{{ $appUrl ? $appUrl.'/admin/reports/low-stock' : 'รายงานสินค้าสต็อกต่ำ' }}</a>
    </p>

    <p style="margin-top: 30px; font-size: 12px; color: #555;">อีเมลนี้ส่งอัตโนมัติ กรุณาอย่าตอบกลับ</p>
</body>
</html>
