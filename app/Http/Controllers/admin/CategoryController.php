<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;
use App\Models\TempImage;
use Illuminate\Support\Facades\File;
use Image;
//use Intervention\Image\Facades\Image As Image;


class CategoryController extends Controller
{
    public function index(Request $request) {
        $categories = Category::latest();

        if(!empty($request->get('keyword'))) {
            $categories = $categories->where('name', 'like', '%'.$request->get('keyword').'%');
        }
        $categories = $categories->paginate(10);
        //dd($categories);

        return view('admin.category.list', compact('categories'));
    }

    public function create() {
        return view('admin.category.create');
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:categories',
        ]);

        if($validator->passes()) {

            $category = new Category();
            $category->name = $request->name;
            $category->slug = $request->slug;
            $category->status = $request->status;
            $category->showHome = $request->showHome;
            $category-> save();

            // Save Image
            if(!empty($request->image_id)) {
                $tempImage = TempImage::find($request->image_id);
                $extArray = explode('.', $tempImage->name);
                $ext = last($extArray);

                $newImageName = $category->id.'.'.$ext;
                $sPath = public_path().'/temp/'.$tempImage->name;
                $dPath = public_path().'/uploads/category/'.$newImageName;
                File::copy($sPath, $dPath);

                // Generate Image
                $dPath = public_path().'/uploads/category/thumb/'.$newImageName;
                $img = Image::make($sPath);
                //$img->resize(450,600);
                $img->fit(450, 600, function($constraint) {
                    $constraint->update();
                });
                $img->save($dPath);

                $category->image = $newImageName;
                $category-> save();
            }

            //$request->session()->flash('success','Your message');
            session()->flash('success','Category added successfully');

            return response()->json([
                'status' => true,
                'message' => 'Category added successfully'
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function edit($categoryId, Request $request) {
       $category = Category::find($categoryId);
       if(empty($category)) {
            return redirect()->route('categories.index');
       }

       return view('admin.category.edit', compact('category'));
    }

    public function update($categoryId, Request $request) {
        $category = Category::find($categoryId);
        if(empty($category)) {

            session()->flash('error', 'Hàng hóa không tồn tại');

            return response()->json([
                'status' => false,
                'notFound' => true,
                'message' => 'Danh mục không tìm thấy'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:categories,slug,' . $category->id . ',id',
        ]);

        if($validator->passes()) {
            // Cập nhật danh mục hiện có thay vì khởi tạo lại
            $category->name = $request->name;
            $category->slug = $request->slug;
            $category->status = $request->status;
            $category->showHome = $request->showHome;
            $oldImage = $category->image;

            // Lưu ảnh
            if(!empty($request->image_id)) {
                $tempImage = TempImage::find($request->image_id);
                if($tempImage) { // Kiểm tra xem tempImage có tồn tại không
                    $extArray = explode('.', $tempImage->name);
                    $ext = last($extArray);

                    $newImageName = $category->id . '-' . time() . '.' . $ext;
                    $sPath = public_path() . '/temp/' . $tempImage->name;
                    $dPath = public_path() . '/uploads/category/' . $newImageName;

                    if(File::exists($sPath)) { // Đảm bảo rằng tệp nguồn tồn tại
                        File::copy($sPath, $dPath);

                        // Tạo ảnh thumb
                        $thumbPath = public_path() . '/uploads/category/thumb/' . $newImageName;
                        $img = Image::make($sPath);
                        $img->fit(450, 600, function($constraint) {
                            $constraint->upsize();
                        });
                        $img->save($thumbPath);

                        $category->image = $newImageName;

                        // Xóa ảnh cũ
                        if($oldImage) {
                            File::delete(public_path() . '/uploads/category/thumb/' . $oldImage);
                            File::delete(public_path() . '/uploads/category/' . $oldImage);
                        }
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => 'Ảnh tạm thời không tìm thấy.'
                        ]);
                    }
                }
            }

            $category->save();

            session()->flash('success', 'Cập nhật danh mục thành công');

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật danh mục thành công'
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }


    public function destroy($categoryId, Request $request) {
        $category = Category::find($categoryId);
        if(empty($category)) {
            session()->flash('error','Hàng hóa không tồn tại');
            return response()->json([
                'status' => true,
                'message' => 'Đã xóa hàng hóa thành công'
            ]);

            //return redirect()->route('categories.index');
        }


        File::delete(public_path() . '/uploads/category/thumb/' . $category->image);
        File::delete(public_path() . '/uploads/category/' . $category->image);

        $category->delete();

        session()->flash('success','Đã xóa thành công');

        return response()->json([
            'status' => true,
            'message' => 'Đã xóa thành công'
        ]);

    }
}
