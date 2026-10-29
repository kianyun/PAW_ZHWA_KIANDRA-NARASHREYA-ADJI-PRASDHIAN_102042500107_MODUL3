<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    /**
     * Menampilkan daftar semua transaksi peminjaman buku.
     */
    public function index()
    {
        $peminjaman = Peminjaman::with(['anggota', 'buku'])
            ->latest()
            ->paginate(10);

        return view('peminjaman.index', compact('peminjaman'));
    }

    /**
     * Menampilkan form peminjaman buku.
     */
    public function create()
    {
        $anggota = Anggota::orderBy('nama')->get();
        // Buku dengan stok 0 tidak boleh dipilih
        $buku = Buku::where('stok', '>', 0)->orderBy('judul')->get();

        return view('peminjaman.create', compact('anggota', 'buku'));
    }

    /**
     * TODO 7: Validasi transaksi, simpan data, dan kurangi stok buku.
     */
    public function store(Request $request)
    {
        $request->validate([
            'anggota_id'     => 'required|exists:anggota,id',
            'buku_id'        => 'required|exists:buku,id',
            'tanggal_pinjam' => 'required|date',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // lockForUpdate mencegah dua transaksi mengambil stok yang sama
                $buku = Buku::lockForUpdate()->findOrFail($request->buku_id);

                if ($buku->stok <= 0) {
                    throw new \Exception('Stok buku "' . $buku->judul . '" sedang habis.');
                }

                Peminjaman::create([
                    'anggota_id'      => $request->anggota_id,
                    'buku_id'         => $request->buku_id,
                    'tanggal_pinjam'  => $request->tanggal_pinjam,
                    'tanggal_kembali' => null,
                    'status'          => 'dipinjam',
                ]);

                $buku->decrement('stok');
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('peminjaman.index')
            ->with('success', 'Transaksi peminjaman berhasil dicatat.');
    }

    /**
     * Menampilkan detail transaksi peminjaman.
     */
    public function show(Peminjaman $peminjaman)
    {
        $peminjaman->load(['anggota', 'buku']);

        return view('peminjaman.show', compact('peminjaman'));
    }

    /**
     * Memproses pengembalian buku: ubah status dan tambah stok.
     */
    public function kembali(Peminjaman $peminjaman)
    {
        if ($peminjaman->status === 'dikembalikan') {
            return back()->with('error', 'Buku ini sudah dikembalikan sebelumnya.');
        }

        DB::transaction(function () use ($peminjaman) {
            $peminjaman->update([
                'status'          => 'dikembalikan',
                'tanggal_kembali' => now()->toDateString(),
            ]);

            $peminjaman->buku()->increment('stok');
        });

        return redirect()->route('peminjaman.index')
            ->with('success', 'Buku berhasil dikembalikan, stok bertambah.');
    }

    /**
     * Menghapus transaksi peminjaman.
     */
    public function destroy(Peminjaman $peminjaman)
    {
        // Jika buku masih dipinjam, stok dikembalikan dulu sebelum dihapus
        DB::transaction(function () use ($peminjaman) {
            if ($peminjaman->status === 'dipinjam') {
                $peminjaman->buku()->increment('stok');
            }

            $peminjaman->delete();
        });

        return redirect()->route('peminjaman.index')
            ->with('success', 'Data peminjaman berhasil dihapus.');
    }
}