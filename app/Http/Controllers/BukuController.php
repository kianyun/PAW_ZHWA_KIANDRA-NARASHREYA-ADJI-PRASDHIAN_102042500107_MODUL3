<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use Illuminate\Http\Request;

class BukuController extends Controller
{
    /**
     * Menampilkan daftar buku.
     */
    public function index()
    {
        $buku = Buku::orderBy('judul')->paginate(10);

        return view('buku.index', compact('buku'));
    }

    /**
     * Menampilkan form tambah buku.
     */
    public function create()
    {
        return view('buku.create');
    }

    /**
     * TODO 5: Validasi dan simpan data buku baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'judul'        => 'required|string|max:150',
            'penulis'      => 'required|string|max:100',
            'penerbit'     => 'required|string|max:100',
            'tahun_terbit' => 'required|integer|min:1900|max:' . date('Y'),
            'stok'         => 'required|integer|min:0',
        ]);

        Buku::create($data);

        return redirect()->route('buku.index')
            ->with('success', 'Data buku berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail buku beserta riwayat peminjamannya.
     */
    public function show(Buku $buku)
    {
        $buku->load('peminjaman.anggota');

        return view('buku.show', compact('buku'));
    }

    /**
     * Menampilkan form edit buku.
     */
    public function edit(Buku $buku)
    {
        return view('buku.edit', compact('buku'));
    }

    /**
     * Memproses perubahan data buku.
     */
    public function update(Request $request, Buku $buku)
    {
        $data = $request->validate([
            'judul'        => 'required|string|max:150',
            'penulis'      => 'required|string|max:100',
            'penerbit'     => 'required|string|max:100',
            'tahun_terbit' => 'required|integer|min:1900|max:' . date('Y'),
            'stok'         => 'required|integer|min:0',
        ]);

        $buku->update($data);

        return redirect()->route('buku.index')
            ->with('success', 'Data buku berhasil diperbarui.');
    }

    /**
     * Menghapus data buku.
     */
    public function destroy(Buku $buku)
    {
        // Buku yang sedang dipinjam tidak boleh dihapus
        if ($buku->peminjaman()->where('status', 'dipinjam')->exists()) {
            return redirect()->route('buku.index')
                ->with('error', 'Buku tidak bisa dihapus karena sedang dipinjam.');
        }

        $buku->delete();

        return redirect()->route('buku.index')
            ->with('success', 'Data buku berhasil dihapus.');
    }
}