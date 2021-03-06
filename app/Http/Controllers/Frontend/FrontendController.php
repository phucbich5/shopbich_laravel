<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Loai;
use App\Mau;
use App\Sanpham;
use DB;
use Mail;
use App\Mail\ContactMailer;
use App\Vanchuyen;
use App\Khachhang;
use App\Donhang;
use App\Thanhtoan;
use App\Chitietdonhang;
use Carbon\Carbon;
use App\Mail\OrderMailer;
// use Illuminate\Support\Facades\DB;

class FrontendController extends Controller
{
    /**
     * Action hiển thị view Trang chủ
     * GET /
     */
    public function index(Request $request)
    {
        // Query top 3 loại sản phẩm (có sản phẩm) mới nhất
        // Eloquent MODEL, Query Builder DB
        $ds_top3_newest_loaisanpham =
            DB::table('cusc_loai')
            ->join('cusc_sanpham', 'cusc_loai.l_ma', '=', 'cusc_sanpham.l_ma')
            ->orderBy('l_capNhat')
            ->take(3)
            ->get();

        // Query tìm danh sách sản phẩm
        $danhsachsanpham = $this->searchSanPham($request);

        // Query Lấy các hình ảnh liên quan của các Sản phẩm đã được lọc
        $danhsachhinhanhlienquan = DB::table('cusc_hinhanh')
            ->whereIn('sp_ma', $danhsachsanpham->pluck('sp_ma')->toArray())
            ->get();

        // Query danh sách Loại
        $danhsachloai = Loai::all();

        // Query danh sách màu
        $danhsachmau = Mau::all();

        // Hiển thị view `frontend.index` với dữ liệu truyền vào
        return view('frontend.index')
            ->with('ds_top3_newest_loaisanpham', $ds_top3_newest_loaisanpham)
            ->with('danhsachsanpham', $danhsachsanpham)
            ->with('danhsachhinhanhlienquan', $danhsachhinhanhlienquan)
            ->with('danhsachmau', $danhsachmau)
            ->with('danhsachloai', $danhsachloai);
    }

    /** * Action hiển thị view Liên hệ * GET /contact */
    public function contact()
    {
        return view('frontend.pages.contact');
    }

    /** 
     * Action gởi email với các lời nhắn nhận được từ khách hàng 
     * POST /lien-he/goi-loi-nhan 
     */
    public function sendMailContactForm(Request $request)
    {
        $input = $request->all();
        Mail::to('phucbich4@gmail.com')->send(new ContactMailer($input));
        return $input;
    }

    /**
     * Action hiển thị view Giới thiệu
     * GET /about
     */
    public function about()
    {
        return view('frontend.pages.about');
    }

    /**
     * Action hiển thị danh sách Sản phẩm
     */
    public function product(Request $request)
    {
        // Query tìm danh sách sản phẩm
        $danhsachsanpham = $this->searchSanPham($request);

        // Query Lấy các hình ảnh liên quan của các Sản phẩm đã được lọc
        $danhsachhinhanhlienquan = DB::table('cusc_hinhanh')
            ->whereIn('sp_ma', $danhsachsanpham->pluck('sp_ma')->toArray())
            ->get();

        // Query danh sách Loại
        $danhsachloai = Loai::all();

        // Query danh sách màu
        $danhsachmau = Mau::all();

        // Hiển thị view `frontend.index` với dữ liệu truyền vào
        return view('frontend.pages.product')
            ->with('danhsachsanpham', $danhsachsanpham)
            ->with('danhsachhinhanhlienquan', $danhsachhinhanhlienquan)
            ->with('danhsachmau', $danhsachmau)
            ->with('danhsachloai', $danhsachloai);
    }

    /**
     * Action hiển thị chi tiết Sản phẩm
     */
    public function productDetail(Request $request, $id)
    {
        $sanpham = SanPham::find($id);

        // Query Lấy các hình ảnh liên quan của các Sản phẩm đã được lọc
        $danhsachhinhanhlienquan = DB::table('cusc_hinhanh')
            ->where('sp_ma', $id)
            ->get();

        // Query danh sách Loại
        $danhsachloai = Loai::all();

        // Query danh sách màu
        $danhsachmau = Mau::all();

        return view('frontend.pages.product-detail')
            ->with('sp', $sanpham)
            ->with('danhsachhinhanhlienquan', $danhsachhinhanhlienquan)
            ->with('danhsachmau', $danhsachmau)
            ->with('danhsachloai', $danhsachloai);
    }

    /**
     * Action hiển thị giỏ hàng
     */
    public function cart(Request $request)
    {
        // Query danh sách hình thức vận chuyển
        $danhsachvanchuyen = Vanchuyen::all();

        // Query danh sách phương thức thanh toán
        $danhsachphuongthucthanhtoan = Thanhtoan::all();

        return view('frontend.pages.shopping-cart')
            ->with('danhsachvanchuyen', $danhsachvanchuyen)
            ->with('danhsachphuongthucthanhtoan', $danhsachphuongthucthanhtoan);
    }
    /**
     * Action Đặt hàng
     */
    public function order(Request $request)
    {
        // dd($request);
        // Data gởi mail
        $dataMail = [];
        try {
            // Tạo mới khách hàng
            $khachhang = new Khachhang();
            $khachhang->kh_taiKhoan = $request->khachhang['kh_taiKhoan'];
            $khachhang->kh_matKhau = bcrypt('123456');
            $khachhang->kh_hoTen = $request->khachhang['kh_hoTen'];
            $khachhang->kh_gioiTinh = $request->khachhang['kh_gioiTinh'];
            $khachhang->kh_email = $request->khachhang['kh_email'];
            $khachhang->kh_ngaySinh = $request->khachhang['kh_ngaySinh'];
            if (!empty($request->khachhang['kh_diaChi'])) {
                $khachhang->kh_diaChi = $request->khachhang['kh_diaChi'];
            }
            if (!empty($request->khachhang['kh_dienThoai'])) {
                $khachhang->kh_dienThoai = $request->khachhang['kh_dienThoai'];
            }
            $khachhang->kh_trangThai = 2; // Khả dụng
            $khachhang->save();
            $dataMail['khachhang'] = $khachhang->toArray();
            // Tạo mới đơn hàng
            $donhang = new Donhang();
            $donhang->kh_ma = $khachhang->kh_ma;
            $donhang->dh_thoiGianDatHang = Carbon::now();
            $donhang->dh_thoiGianNhanHang = $request->donhang['dh_thoiGianNhanHang'];
            $donhang->dh_nguoiNhan = $request->donhang['dh_nguoiNhan'];
            $donhang->dh_diaChi = $request->donhang['dh_diaChi'];
            $donhang->dh_dienThoai = $request->donhang['dh_dienThoai'];
            $donhang->dh_nguoiGui = $request->donhang['dh_nguoiGui'];
            $donhang->dh_loiChuc = $request->donhang['dh_loiChuc'];
            $donhang->dh_daThanhToan = 0; //Chưa thanh toán
            $donhang->nv_xuLy = 1; //Mặc định nhân viên đầu tiên
            $donhang->nv_giaoHang = 1; //Mặc định nhân viên đầu tiên
            $donhang->dh_trangThai = 1; //Nhận đơn
            $donhang->vc_ma = $request->donhang['vc_ma'];
            $donhang->tt_ma = $request->donhang['tt_ma'];
            $donhang->save();
            $dataMail['donhang'] = $donhang->toArray();
            // Lưu chi tiết đơn hàng
            foreach ($request->giohang['items'] as $sp) {
                $chitietdonhang = new Chitietdonhang();
                $chitietdonhang->dh_ma = $donhang->dh_ma;
                $chitietdonhang->sp_ma = $sp['_id'];
                $chitietdonhang->m_ma = 1;
                $chitietdonhang->ctdh_soLuong = $sp['_quantity'];
                $chitietdonhang->ctdh_donGia = $sp['_price'];
                $chitietdonhang->save();
                $dataMail['donhang']['chitiet'][] = $chitietdonhang->toArray();
                $dataMail['donhang']['giohang'][] = $sp;
            }
            // Gởi mail khách hàng
            // dd($dataMail);
            Mail::to($khachhang->kh_email)
                ->send(new OrderMailer($dataMail));
        } catch (ValidationException $e) {
            return response()->json(array(
                'code'  => 500,
                'message' => $e,
                'redirectUrl' => route('frontend.home')
            ));
        } catch (\Exception $e) {
            throw $e;
        }
        return response()->json(array(
            'code'  => 200,
            'message' => 'Tạo đơn hàng thành công!',
            'redirectUrl' => route('frontend.orderFinish')
        ));
    }
    /**
     * Action Hoàn tất Đặt hàng
     */
    public function orderFinish()
    {
        return view('frontend.pages.order-finish');
    }
    public function searchAjax(Request $request)
    {
        $output = '';
        $product = Sanpham::where('sp_ten', 'like', '%' . $request->keyword . '%')->get();



        foreach ($product as $pro) {
            $output .= '
                <li class="item_search">
                    <a onclick="window.location="
                        href="/san-pham/' . $pro->sp_ma . '" style="display:flex">
                        <img width="60"  height="60"
                        src="/uploads/' . $pro->sp_hinh . '">
                        <div class="ml-2 d-flex align-items-center">
                            <div>
                                <p class="name m-0">' . $pro->sp_ten . '</p>
                                <p class="price m-0">' . $pro->sp_giaBan . ' đ</p>
                            </div>
                        </div>
                    </a>
                </li>';
        }


        return response()->json($output);
    }
    public function searchAjax_product(Request $request)
    {
        $output = '';
        $product = Sanpham::where('sp_ten', 'like', '%' . $request->keyword . '%')->get();
        foreach ($product as $pro) {
            $output .= '<div class="col-6 col-sm-6 col-md-4 col-lg-3 p-b-35 isotope-item loai-' . $pro->l_ma . '" style="padding:0;border:1px solid #f3f3f3">
                 
                <div class="block2">
                    <div class="block2-pic hov-img0">
                        <img src="/uploads/' . $pro->sp_hinh . '" alt="IMG-PRODUCT">

                        <a href="/san-pham/' . $pro->sp_ma . '"
                            class="block2-btn flex-c-m stext-103 cl2 size-102 bg0 bor2 hov-btn1 p-lr-15 trans-04 js-show-modal"
                            >
                            Xem nhanh
                        </a>
                    </div>

                    <div class="block2-txt flex-w flex-t p-t-14">
                        <div class="block2-txt-child1 flex-col-l">
                            <a href="/san-pham/' . $pro->sp_ma . '"
                                class="stext-104 cl4 hov-cl1 trans-04 js-name-b2 p-b-6">
                                ' . mb_substr($pro->sp_ten, 0, 40) . '...' . '
                            </a>

                            <span class="stext-105 cl3" style="width: 100%;">
                                <span>Chỉ Từ : </span><span class="text-bold text-danger">' . number_format($pro->sp_giaBan, 0, '.', ".") . '</span><span> đ</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>';
        }


        return response()->json($output);
    }
    /**
     * Hàm query danh sách sản phẩm theo nhiều điều kiện
     */
    private function searchSanPham(Request $request)
    {
        $query = DB::table('cusc_sanpham')->select('*');
        // $query = DB::table('cusc_sanpham')->paginate(12);
        // Kiểm tra điều kiện `searchByLoaiMa`
        $searchByLoaiMa = $request->query('searchByLoaiMa');
        if ($searchByLoaiMa != null) {
        }
        $data = $query->simplePaginate(12);


        // $data = $query->get();
        return $data;
    }

    public function searchSanPham2(Request $request)
    {
        $query = DB::table('cusc_sanpham')->select('*');
        // $query = DB::table('cusc_sanpham')->paginate(12);
        // Kiểm tra điều kiện `searchByLoaiMa`
        $searchByLoaiMa = $request->query('searchByLoaiMa');
        if ($searchByLoaiMa != null) {
        }
        $data = $query->simplePaginate(12);
        // $data = $query->get  ();
        // return $data;

        $template = '';


        foreach ($data as $index => $sp) {
            $template = $template . '<div class="col-6 col-sm-6 col-md-4 col-lg-3 p-b-35 isotope-item loai-' . $sp->l_ma . '" style="padding:0;border:1px solid #f3f3f3">
                 
                    <div class="block2">
                        <div class="block2-pic hov-img0">
                            <img src="/uploads/' . $sp->sp_hinh . '" alt="IMG-PRODUCT">

                            <a href="/san-pham/' . $sp->sp_ma . '"
                                class="block2-btn flex-c-m stext-103 cl2 size-102 bg0 bor2 hov-btn1 p-lr-15 trans-04 js-show-modal"
                          ">
                                Xem nhanh
                            </a>
                        </div>

                        <div class="block2-txt flex-w flex-t p-t-14">
                            <div class="block2-txt-child1 flex-col-l">
                                <a href="/san-pham/' . $sp->sp_ma . '"
                                    class="stext-104 cl4 hov-cl1 trans-04 js-name-b2 p-b-6">
                                    ' . mb_substr($sp->sp_ten, 0, 40) . '...' . '
                                </a>

                                <span class="stext-105 cl3" style="width: 100%;">
                                    <span>Chỉ Từ : </span><span class="text-bold text-danger">' . number_format($sp->sp_giaBan, 0, '.', ".") . '</span><span> đ</span>
                                </span>
                            </div>
                        </div>
                    </div>
                   
                </div>';
        }

        return json_encode(array('template' => $template));
    }




    /**
     * Hàm query danh sách sản phẩm theo nhiều điều kiện
     */
    /*
private function searchSanPham(Request $request)
{
  // 1. Tạo câu lệnh SQL
  $sqlSelect = <<<EOT
  SELECT * FROM cusc_sanpham
  WHERE 1=1
EOT;
  
  // 2. Tạo câu lệnh WHERE điều kiện
  $sqlWhere = '';

  // Kiểm tra điều kiện `searchByLoaiMa`
  $searchByLoaiMa = $request->query('searchByLoaiMa');
  if (!empty($searchByLoaiMa)) {
    $sqlWhere .= " AND sp_ma LIKE '%$searchByLoaiMa%'";
  }

  // Kiểm tra điều kiện `searchByLoaiTen`
  $searchByLoaiTen = $request->query('searchByLoaiTen');
  if (!empty($searchByLoaiTen)) {
    $sqlWhere .= " AND sp_ten LIKE '%$searchByLoaiTen%'";
  }

  // ... các điều kiện khác
  
  // 3. Thực thi câu lệnh
  $data = DB::select($sqlSelect . $sqlWhere)->get();
  return $data;
}
*/
}
